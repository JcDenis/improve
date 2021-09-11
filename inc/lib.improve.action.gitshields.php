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

class ImproveActionGitshields extends ImproveAction
{
    private $stop_scan = false;
    protected $bloc_pattern = [
        'remove' => '/\[!\[Release(.*)LICENSE\)/ms',
        'target' => '/^([^\n]+)[\r\n|\n]{1,}/ms'
    ];
    protected $bloc_content = [
        'release' => '[![Release](https://img.shields.io/github/v/release/%username%/%module%)](https://github.com/%username%/%module%/releases)',
        'date' => '[![Date](https://img.shields.io/github/release-date/%username%/%module%)](https://github.com/%username%/%module%/releases)',
        'issues' => '[![Issues](https://img.shields.io/github/issues/%username%/%module%)](https://github.com/%username%/%module%/issues)',
        'dotclear' => '[![Dotclear](https://img.shields.io/badge/dotclear-v%dotclear%-blue.svg)](https://fr.dotclear.org/download)',
        'dotaddict' => '[![Dotclear](https://img.shields.io/badge/dotaddict-official-green.svg)](https://%type%s.dotaddict.org/dc2/details/%module%)',
        'license' => '[![License](https://img.shields.io/github/license/%username%/%module%)](https://github.com/%username%/%module%/blob/master/LICENSE)'
    ];

    protected function init(): bool
    {
        $this->setProperties([
            'id'       => 'gitshields',
            'name'     => __('Fix shields badges'),
            'desc'     => __('Add and maintain shields.io badges to the REDAME.md file'),
            'priority' => 380,
            'config'   => true,
            'types'    => ['plugin', 'theme']
        ]);

        return true;
    }

    public function isConfigured(): bool
    {
        return !empty($this->getPreference('username'));
    }

    public function configure($url): ?string
    {
        if (!empty($_POST['save']) && !empty($_POST['username'])) {
            $this->setPreferences([
                'username' => (string) $_POST['username'],
                'dotaddict' => !empty($_POST['dotaddict'])
            ]);
            $this->redirect($url);
        }

        return '
        <p><label for="username">' . __('Your Github user name :') . '</label>' .
        form::field('username', 60, 100, $this->getPreference('username')) . '
        </p><p class="form-note">' . __('Used in your Github URL: http://github.com/username/module_id.') . '<br />' .
        __('If you have badges not created by this tool in the README.md file you should remove them manually.') . '</p>

        <p><label for="dotaddict">' . 
        form::checkbox('dotaddict', 1, !empty($this->getPreference('dotaddict'))) . ' '.
        __('Include Dotaddict badge') . '</label>
        </p><p class="form-note">' . __('If your plugin or theme is on Dotaddict, you can add a badge to link to its details in Dotaddict.') . '</p>';
    }

    public function openModule(string $module_type, array $module_info): ?bool
    {
        $this->type = $module_type;
        $this->module = $module_info;
        $this->replaceInfo();

        return null;
    }

    public function readFile($path, $extension, &$content): ?bool
    {
        if ($this->stop_scan || !preg_match('/(.*?)README\.md$/i', $path)) {
            return null;
        }

        $clean = $this->deleteShieldsBloc($content);
        $content = $this->writeShieldsBloc($clean);
        $this->stop_scan = true;

        return true;
    }

    private function replaceInfo()
    {
        $username = $this->getPreference('username');
        $module = $this->module['id'];
        $type = $this->module['type'];
        $dotclear = $this->getDotclearVersion();

        $bloc = [];
        foreach($this->bloc_content as $k => $v) {
            if ($k == 'dotaddict' && empty($this->getPreference('dotaddict'))) {
                continue;
            }
            $bloc[$k] = trim(str_replace(
                ['%username%', '%module%', '%dotclear%', '%type%', "\r\n", "\n"],
                [$username, $module, $dotclear, $type, '', ''],
                $v
            ));
        }
        $this->bloc = $bloc;
    }

    private function getDotclearVersion()
    {
        $version = null;
        $module = $this->module;
        if (!empty($module['requires']) && is_array($module['requires'])) {
            foreach ($module['requires'] as $req) {
                if (!is_array($req)) {
                    $req = [$req];
                }
                if ($req[0] == 'core') {
                    $version = $req[1];
                    break;
                }
            }
        }
        return $version ?: $this->core->getVersion('core');
    }

    private function writeShieldsBloc($content)
    {
        return preg_replace(
            $this->bloc_pattern['target'],
            '$1' . "\n\n" . trim(implode("\n", $this->bloc)) . "\n\n",
            $content,
            1
        );
    }

    private function deleteShieldsBloc($content)
    {
        return preg_replace(
            $this->bloc_pattern['remove'],
            "\n\n",
            $content
        );
    }
}