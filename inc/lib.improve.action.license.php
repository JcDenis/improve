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

class ImproveActionLicense extends ImproveAction
{
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
    private $action_version = [];
    private $action_full = [];
    private $action_bloc = [];

    private $bloc = '';
    private $stop_scan = false;

    protected function init(): bool
    {
        $this->setProperties([
            'id'       => 'license',
            'name'     => __('Fix license'),
            'desc'     => __('Add or remove license bloc at the begining of file and full license file to module root'),
            'priority' => 320,
            'config'   => true,
            'types'    => ['plugin', 'theme']
        ]);

        $this->action_version = [
            __('no version selected')                          => '',
            __('gpl2 - GNU General Public License v2')         => 'gpl2',
            __('gpl3 - GNU General Public License v3')         => 'gpl3',
            __('lgpl3 - GNU Lesser General Public License v3') => 'lgpl3',
            __('Massachusetts Institute of Technolog mit')     => 'mit'
        ];
        $this->action_full = [
            __('Do nothing')                    => 0,
            __('Add file if it does not exist') => 'create',
            __('Add file even if it exists')    => 'overwrite',
            __('Remove license file')           => 'remove'
        ];
        $this->action_bloc = [
            __('Do nothing')                    => 0,
            __('Add bloc if it does not exist') => 'create',
            __('Add bloc even if it exists')    => 'overwrite' ,
            __('Remove old style bloc')         => 'remove'
        ];

        return true;
    }

    public function isConfigured(): bool
    {
        return true;
    }

    public function configure($url): ?string
    {
        if (!empty($_POST['save'])) {
            $this->setPreferences([
                'action_version'  => !empty($_POST['action_version']) ? $_POST['action_version'] : '',
                'action_full'     => !empty($_POST['action_full']) ? $_POST['action_full'] : '',
                'action_php'      => !empty($_POST['action_php']) ? $_POST['action_php'] : '',
                'action_js'       => !empty($_POST['action_js']) ? $_POST['action_js'] : '',
                'exclude_locales' => !empty($_POST['lb_exclude_locales']),
                'use_custom_bloc' => !empty($_POST['lb_use_custom_bloc']),
                'custom_bloc'     => !empty($_POST['lb_custom_bloc']) ? $_POST['lb_custom_bloc'] : ''
            ]);
            $this->redirect($url);
        }

        return '
        <p class="info">' . __('This manages old style bloc, it is better to use modern phpDdoc style.') . '</p>
        <p class="field"><label for="action_version">' . __('License version:') . '</label>' .
        form::combo('action_version', $this->action_version, $this->getPreference('action_version')) . '
        </p>

        <p class="field"><label for="action_full">' . __('Full license file:') . '</label>' .
        form::combo('action_full', $this->action_full, $this->getPreference('action_full')) . '
        </p>

        <p class="field"><label for="action_php">' . __('License bloc on php file:') . '</label>' .
        form::combo('action_php', $this->action_bloc, $this->getPreference('action_php')) . '
        </p>

        <p class="field"><label for="action_js">' . __('License bloc on js file:') . '</label>' .
        form::combo('action_js', $this->action_bloc, $this->getPreference('action_js')) . '
        </p>

        <p><label class="classic" for="lb_exclude_locales">' .
        form::checkbox('lb_exclude_locales', 1, $this->getPreference('exclude_locales')) . ' ' .
        __('Do not add license block to files from locales folder') .
        '</label></p>

        <p><label class="classic" for="lb_use_custom_bloc">' .
        form::checkbox('lb_use_custom_bloc', 1, $this->getPreference('use_custom_bloc')) .' ' .
        __('Use custom bloc for file headers:') .
        '</label></p>

        <p class="area">' .
        form::textarea('lb_custom_bloc', 50, 10, html::escapeHTML($this->getPreference('custom_bloc'))) . '
        </p><p class="form-note">' . 
        sprintf(
            __('You can use wildcards %s') , 
            '%year%, %module_id%, %module_name%, %module_author%, %module_type%, %user_cn%, %user_name%, %user_email%, %user_url%'
        ) . '<br />' . __('Do not put structural elements to the begining of lines.') . '</p>';
    }

    public function openModule(string $module_type, array $module_info): ?bool
    {
        $this->type = $module_type;
        $this->module = $module_info;

        $this->replaceInfo();
        if (in_array($this->getPreference('action_full'), ['create', 'overwrite'])) {
            if (empty($this->getPreference('action_version'))) {
                self::notice(__('no full license type selected'), false);
            } else {
                $this->writeFullLicense();
            }
        }

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
        if (
            $this->stop_scan || !in_array($extension, ['php', 'js']) || self::hasNotice()
            || empty($this->getPreference('action_php')) && empty($this->getPreference('action_js'))
        ) {
            return null;
        }

        if (
            !empty($this->getPreference('action_version'))
            || !empty($this->getPreference('use_custom_bloc')) && !empty($this->getPreference('custom_bloc'))
        ) {
            if ($extension == 'php' && in_array($this->getPreference('action_php'), ['create', 'overwrite'])) {
                $content = $this->writePhpBloc($content);
            }
            if ($extension == 'js' && in_array($this->getPreference('action_js'), ['create', 'overwrite'])) {
                $content = $this->writeJsBloc($content);
            }
        }
        if ($extension == 'php' && $this->getPreference('action_php') == 'remove') {
            $content = $this->deletePhpBloc($content);
        }
        if ($extension == 'js' && $this->getPreference('action_js') == 'remove') {
            $content = $this->deleteJsBloc($content);
        }

        return true;
    }

    public function closeModule(string $module_type, array $module_info): ?bool
    {
        if ('remove' == $this->getPreference('action_full')) {
            $this->deleteFullLicense();
        }

        return null;
    }

    private function replaceInfo()
    {
        $bloc = '';
        if (!empty($this->getPreference('custom_bloc')) && !empty($this->getPreference('use_custom_bloc'))) {
            $bloc = $this->getPreference('custom_bloc');
        } elseif ($this->getPreference('version')) {
            try {
                $bloc = file_get_contents(
                    dirname(__FILE__) . '/license/' . $this->getPreference('version') . '.head.txt'
                );
            } catch (Exception $e) {
                self::notice(__('failed to load license bloc'));

                return null;
            }
        }
        if (empty($bloc)) {
            self::notice(__('license bloc is empty'), false);

            return null;
        }

        try {
            $this->bloc = str_replace(
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
            );
        } catch (Exception $e) {
            self::notice(__('failed to parse license bloc'));

            return null;
        }
    }

    private function writeFullLicense()
    {
        if (file_exists($this->module['root'] . '/LICENSE') && $this->getPreference('action_full') != 'overwrite') {
            self::notice(__('full license already exists'), false);
        }
        try {
            $full = file_get_contents(dirname(__FILE__) . '/license/' . $this->getPreference('action_version') . '.full.txt');
            if (empty($full)) {
                self::notice(__('failed to load full license'));

                return null;
            }
            files::putContent($this->module['root'] . '/LICENSE', $full);
        } catch (Exception $e) {
            self::notice(__('failed to write full license'));

            return null;
        }

        return true;
    }

    private function deleteFullLicense()
    {
        if (!files::isDeletable($this->module['root'] . '/LICENSE')) {
            self::notice(__('full license is not deletable'), false);

            return null;
        }
        if (!@unlink($this->module['root'] . '/LICENSE')) {
            self::notice(__('failed to delete full license'), false);

            return null;
        }

        return true;
    }

    private function writePhpBloc($content)
    {
        $clean = $this->deletePhpBloc($content);

        if ($clean != $content && 'overwrite' != $this->getPreference('action_php')) {

            return $content;
        }

        return preg_replace(
            '/(\<\?php)/',
            '<?php' .
            "\n# -- BEGIN LICENSE BLOCK ----------------------------------\n" .
            "#\n" .
            '# ' . str_replace("\n", "\n# ", trim($this->bloc)).
            "\n#" .
            "\n# -- END LICENSE BLOCK ------------------------------------\n",
            $clean,
            1
        );
    }

    private function deletePhpBloc($content)
    {
        return preg_replace(
            '/((# -- BEGIN LICENSE BLOCK ([-]+))(.*?)(# -- END LICENSE BLOCK ([-]+))([\n|\r\n]+))/msi',
            "\n",
            $content
        );
    }

    private function writeJsBloc($content)
    {
        $clean = $this->deleteJsBloc($content);

        if ($clean != $content && 'overwrite' == $this->getPreference('action_js')) {

            return $content;
        }

        return 
            "/* -- BEGIN LICENSE BLOCK ----------------------------------\n" .
            " *\n" .
            ' * ' . str_replace("\n", "\n * ", trim($this->bloc)).
            "\n *" .
            "\n * -- END LICENSE BLOCK ------------------------------------*/\n\n" .
            $clean;
    }

    private function deleteJsBloc($content)
    {
        return preg_replace(
            '/((\/\* -- BEGIN LICENSE BLOCK ([-]+))(.*?)(\* -- END LICENSE BLOCK ([-]+)\*\/)([\n|\r\n]+))/msi',
            '',
            $content
        );
    }
}