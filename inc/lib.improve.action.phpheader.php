<?php
/**
 * @brief improve, a plugin for Dotclear 2
 * 
 * @package Dotclear
 * @subpackage Plugin
 * 
 * @author Jean-Christian Denis and contributors
 * 
 * @copyright Jean-Christian Denis
 * @copyright GPL-2.0 https://www.gnu.org/licenses/gpl-2.0.html
 */

class ImproveActionPhpheader extends ImproveAction
{
    private static $exemple = "
@brief %module_id%, a %module_type% for Dotclear 2

@package Dotclear
@subpackage \u%module_type%

@author %module_author%

@copyright %user_cn%
@copyright GPL-2.0 https://www.gnu.org/licenses/gpl-2.0.html";

    private $bloc_wildcards = [
        '%year%',
        '%module_id%',
        '%module_name%',
        '%module_author%',
        '%module_type%',
        '%user_cn%',
        '%user_name%',
        '%user_email%',
        '%user_url%'
    ];
    private $bloc_action = [];

    private $bloc_content = '';
    private $stop_scan = false;

    protected function init(): bool
    {
        $this->setProperties([
            'id'       => 'phpheader',
            'name'     => __('Fix PHP header'),
            'desc'     => __('Add or remove phpdoc header bloc from php file'),
            'priority' => 340,
            'config'   => true,
            'types'    => ['plugin', 'theme']
        ]);

        $this->action_bloc = [
            __('Do nothing')                       => 0,
            __('Add bloc if it does not exist')    => 'create',
            __('Add and overwrite bloc')           => 'overwrite' ,
            __('Overwrite bloc only if it exists') => 'replace',
            __('Remove existing bloc header')      => 'remove'
        ];

        return true;
    }

    public function isConfigured(): bool
    {
        return !empty($this->getPreference('bloc_action')) || !empty($this->getPreference('remove_old'));
    }

    public function configure($url): ?string
    {
        if (!empty($_POST['save'])) {
            $this->setPreferences([
                'bloc_action'     => !empty($_POST['bloc_action']) ? $_POST['bloc_action'] : '',
                'bloc_content'    => !empty($_POST['bloc_content']) ? $_POST['bloc_content'] : '',
                'remove_old'      => !empty($_POST['remove_old']),
                'exclude_locales' => !empty($_POST['exclude_locales'])
            ]);
            $this->redirect($url);
        }

        return '
        <p><label for="bloc_action">' . __('Action:') . '</label>' .
        form::combo('bloc_action', $this->action_bloc, $this->getPreference('bloc_action')) . '
        </p>

        <p><label class="classic" for="remove_old">' .
        form::checkbox('remove_old', 1, $this->getPreference('remove_old')) . ' ' .
        __('Remove old style bloc header (using #)') .
        '</label></p>

        <p><label class="classic" for="exclude_locales">' .
        form::checkbox('exclude_locales', 1, $this->getPreference('exclude_locales')) . ' ' .
        __('Do not add bloc to files from "locales" and "libs" folder') .
        '</label></p>

        <p>' . __('Bloc content:') . '</p>
        <p class="area">' .
        form::textarea('bloc_content', 50, 10, html::escapeHTML($this->getPreference('bloc_content'))) . '
        </p><p class="form-note">' . 
        sprintf(
            __('You can use wildcards %s') , 
            '%year%, %module_id%, %module_name%, %module_author%, %module_type%, %user_cn%, %user_name%, %user_email%, %user_url%'
        ) . '<br />' . __('Do not put structural elements to the begining of lines.') . '</p>' .
        '<div class="fieldset box"><h4>' . __('Exemple') .'</h4><pre class="code">' . self::$exemple . '</pre></div>';
    }

    public function openModule(string $module_type, array $module_info): ?bool
    {
        $this->type = $module_type;
        $this->module = $module_info;
        $this->replaceInfo();

        return null;
    }

    public function openDirectory(string $path): ?bool
    {
        $this->stop_scan = false;
        if (!empty($this->getPreference('exclude_locales')) && preg_match('/\/(locales|libs)(\/.*?|)$/', $path)) {
            $this->stop_scan = true;
        }

        return null;
    }

    public function readFile($path, $extension, &$content): ?bool
    {
        if ($this->stop_scan || $extension !='php' || self::hasNotice()) {
            return null;
        }

        if (!empty($this->getPreference('remove_old'))) {
            $content = $this->deleteOldBloc($content);
        }
        if (empty($this->getPreference('bloc_action'))) {

            return null;
        }
        $clean = $this->deleteDocBloc($content);
        if ($this->getPreference('bloc_action') == 'remove') {
            $content = $clean;

            return null;
        }
        if ($content != $clean && $this->getPreference('bloc_action') == 'create') {

            return null;
        }
        if ($content == $clean && $this->getPreference('bloc_action') == 'replace') {

            return null;
        }

        $content = $this->writeDocBloc($clean);

        return true;
    }

    private function replaceInfo()
    {
        $bloc = trim($this->getPreference('bloc_content'));

        if (empty($bloc)) {
            self::notice(__('bloc is empty'), false);

            return null;
        }

        $bloc = trim(str_replace("\r\n", "\n", $bloc));

        try {
            $this->bloc = preg_replace_callback(
                // use \u in bloc content for first_upper_case
                '/(\\\u([a-z]{1}))/', 
                function($str) { return ucfirst($str[2]); }, 
                str_replace(
                    $this->bloc_wildcards,
                    [
                        date('Y'),
                        $this->module['id'],
                        $this->module['name'],
                        $this->module['author'],
                        $this->module['type'],
                        $this->core->auth->getInfo('user_cn'),
                        $this->core->auth->getinfo('user_name'),
                        $this->core->auth->getInfo('user_email'),
                        $this->core->auth->getInfo('user_url')
                    ],
                    $bloc
                )
            );
        } catch (Exception $e) {
            self::notice(__('failed to parse bloc'));

            return null;
        }
    }

    private function writeDocBloc($content)
    {
        return preg_replace(
            '/^(\<\?php[\n|\r\n]+)/',
            '<?php' . "\n/**\n * " . str_replace("\n", "\n * ", trim($this->bloc)) . "\n */\n\n",
            $content,
            1
        );
    }

    private function deleteDocBloc($content)
    {
        return preg_replace(
            '/^(\<\?php\s*[\n|\r\n]{0,1}\s*\/\*\*.*?\s*\*\/\s*[\n|\r\n]+)/msi',
            "<?php\n",
            $content
        );
    }

    private function deleteOldBloc($content)
    {
        return preg_replace(
            '/((# -- BEGIN LICENSE BLOCK ([-]+))(.*?)(# -- END LICENSE BLOCK ([-]+))([\n|\r\n]{1,}))/msi',
            "",
            $content
        );
    }
}