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
    /** @var string Exemple of header */
    private static $exemple = <<<EOF
@brief %module_id%, a %module_type% for Dotclear 2

@package Dotclear
@subpackage \u%module_type%

@author %module_author%

@copyright %user_cn%
@copyright GPL-2.0 https://www.gnu.org/licenses/gpl-2.0.html
EOF;

    /** @var array<string> Allowed bloc replacement */
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

    /** @var array Allowed action for header */
    private $action_bloc = [];

    /** @var string Parsed bloc */
    private $bloc = '';

    /** @var boolean Stop parsing files */
    private $stop_scan = false;

    protected function init(): bool
    {
        $this->setProperties([
            'id'       => 'phpheader',
            'name'     => __('PHP header'),
            'desc'     => __('Add or remove phpdoc header bloc from php file'),
            'priority' => 340,
            'config'   => true,
            'types'    => ['plugin', 'theme']
        ]);

        $this->action_bloc = [
            __('Do nothing')                       => 0,
            __('Add bloc if it does not exist')    => 'create',
            __('Add and overwrite bloc')           => 'overwrite',
            __('Overwrite bloc only if it exists') => 'replace',
            __('Remove existing bloc header')      => 'remove'
        ];

        return true;
    }

    public function isConfigured(): bool
    {
        return !empty($this->getSetting('bloc_action')) || !empty($this->getSetting('remove_old'));
    }

    public function configure($url): ?string
    {
        if (!empty($_POST['save'])) {
            $this->setSettings([
                'bloc_action'     => !empty($_POST['bloc_action']) ? $_POST['bloc_action'] : '',
                'bloc_content'    => !empty($_POST['bloc_content']) ? $_POST['bloc_content'] : '',
                'remove_old'      => !empty($_POST['remove_old']),
                'exclude_locales' => !empty($_POST['exclude_locales'])
            ]);
            $this->redirect($url);
        }

        return '
        <p><label for="bloc_action">' . __('Action:') . '</label>' .
        form::combo('bloc_action', $this->action_bloc, $this->getSetting('bloc_action')) . '
        </p>

        <p><label class="classic" for="remove_old">' .
        form::checkbox('remove_old', 1, $this->getSetting('remove_old')) . ' ' .
        __('Remove old style bloc header (using #)') .
        '</label></p>

        <p><label class="classic" for="exclude_locales">' .
        form::checkbox('exclude_locales', 1, $this->getSetting('exclude_locales')) . ' ' .
        __('Do not add bloc to files from "locales" and "libs" folder') .
        '</label></p>

        <p>' . __('Bloc content:') . '</p>
        <p class="area">' .
        form::textarea('bloc_content', 50, 10, html::escapeHTML($this->getSetting('bloc_content'))) . '
        </p><p class="form-note">' .
        sprintf(
            __('You can use wildcards %s'),
            '%year%, %module_id%, %module_name%, %module_author%, %module_type%, %user_cn%, %user_name%, %user_email%, %user_url%'
        ) . '<br />' . __('Do not put structural elements to the begining of lines.') . '</p>' .
        '<div class="fieldset box"><h4>' . __('Exemple') . '</h4><pre class="code">' . self::$exemple . '</pre></div>';
    }

    public function openModule(): ?bool
    {
        $bloc = trim($this->getSetting('bloc_content'));

        if (empty($bloc)) {
            $this->setWarning(__('bloc is empty'));

            return null;
        }

        $bloc = trim(str_replace("\r\n", "\n", $bloc));

        try {
            $this->bloc = (string) preg_replace_callback(
                // use \u in bloc content for first_upper_case
                '/(\\\u([a-z]{1}))/',
                function ($str) {
                    return ucfirst($str[2]);
                },
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
                    (string) $bloc
                )
            );
            $this->setSuccess(__('Prepare header info'));

            return null;
        } catch (Exception $e) {
            $this->setError(__('Failed to parse bloc'));

            return null;
        }
    }

    public function openDirectory(): ?bool
    {
        $skipped         = $this->stop_scan;
        $this->stop_scan = false;
        if (!empty($this->getSetting('exclude_locales')) && preg_match('/\/(locales|libs)(\/.*?|)$/', $this->path_full)) {
            if (!$skipped) {
                $this->setSuccess(__('Skip directory'));
            }
            $this->stop_scan = true;
        }

        return null;
    }

    public function readFile(&$content): ?bool
    {
        if ($this->stop_scan || $this->path_extension != 'php' || $this->hasError()) {
            return null;
        }

        if (!empty($this->getSetting('remove_old'))) {
            $content = $this->deleteOldBloc($content);
        }
        if (empty($this->getSetting('bloc_action'))) {
            return null;
        }
        $clean = $this->deleteDocBloc($content);
        if ($this->getSetting('bloc_action') == 'remove') {
            $content = $clean;

            return null;
        }
        if ($content != $clean && $this->getSetting('bloc_action') == 'create') {
            return null;
        }
        if ($content == $clean && $this->getSetting('bloc_action') == 'replace') {
            return null;
        }

        $content = $this->writeDocBloc($clean);

        return true;
    }

    /**
     * Write bloc content in file content
     *
     * @param  string $content Old content
     * @return string          New content
     */
    private function writeDocBloc(string $content): string
    {
        $res = preg_replace(
            '/^(\<\?php[\n|\r\n]+)/',
            '<?php' . "\n/**\n * " . str_replace("\n", "\n * ", trim($this->bloc)) . "\n */\n",
            $content,
            1,
            $count
        );
        if ($count && $res) {
            $res = str_replace("\n * \n", "\n *\n", $res);
            $this->setSuccess(__('Write new doc bloc content'));
        }

        return (string) $res;
    }

    /**
     * Delete bloc content in file content
     *
     * @param  string $content Old content
     * @return string          New content
     */
    private function deleteDocBloc(string $content): string
    {
        $res = preg_replace(
            '/^(\<\?php\s*[\n|\r\n]{0,1}\s*\/\*\*.*?\s*\*\/\s*[\n|\r\n]+)/msi',
            "<?php\n",
            $content,
            -1,
            $count
        );
        if ($count) {
            $this->setSuccess(__('Delete old doc bloc content'));
        }

        return (string) $res;
    }

    /**
     * Delete old style bloc content in file content
     *
     * @param  string $content Old content
     * @return string          New content
     */
    private function deleteOldBloc(string $content): string
    {
        $res = preg_replace(
            '/((# -- BEGIN LICENSE BLOCK ([-]+))(.*?)(# -- END LICENSE BLOCK ([-]+))([\n|\r\n]{1,}))/msi',
            '',
            $content,
            -1,
            $count
        );
        if ($count) {
            $this->setSuccess(__('Delete old style bloc content'));
        }

        return (string) $res;
    }
}
