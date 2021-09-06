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

class ImproveActionDcstore extends ImproveAction
{
    protected function init(): bool
    {
        $this->setProperties([
            'id' => 'dcstore',
            'name' => __('Fix dcstore.xml'),
            'desc' => __('Re-create dcstore.xml file according to _define.php variables'),
            'priority' => 420,
            'config' => true,
            'types' => ['plugin', 'theme']
        ]);

        return true;
    }

    public function isConfigured(): bool
    {
        return !empty($this->getPreference('pattern'));
    }

    public function configure($url): ?string
    {
        if (!empty($_POST['save']) && !empty($_POST['dcstore_pattern'])) {
            $this->setPreferences('pattern', (string) $_POST['dcstore_pattern']);
            $this->redirect($url);
        }

        return  
        '<p class="info">' . __('File will be overwritten if it exists') . '</p>' .
        '<p><label class="classic" for="dcstore_pattern">' . 
        __('Predictable URL to zip file on the external repository') . '<br />' .
        form::field('dcstore_pattern', 160, 255, $this->getPreference('pattern')) . '</label>' .
        '</p>' .
        '<p class="form-note">' . 
        sprintf(__('You can use widcards %s'), '%author%, %type%, %id%, %version%.') . 
        '<br /> ' .
        __('For exemple on github https://github.com/MyGitName/%id%/releases/download/v%version%/%type%-%id%.zip') . 
        '<br />' .
        __('Note on github, you must create a release and join to it the module zip file.') . '
        </p>';
    }

    public function openModule($module_type, $module_info): ?bool
    {
        $this->type = $module_type;
        $this->module = $module_info;

        $content = self::generateXML($module_info['id'], $module_info, $this->getPreference('pattern'));
        if (self::hasNotice()) {

            return false;
        }
        try {
            files::putContent($module_info['sroot'] . '/dcstore.xml', $content);
        } catch(Exception $e) {
            self::notice(__('Failed to write dcstore.xml file'));

            return false;
        }

        return true;
    }

    public static function generateXML($id, $module, $file_pattern)
    {
        if (!is_array($module) || empty($module)) {
            return false;
        }

        $xml = ['<modules xmlns:da="http://dotaddict.org/da/">'];

        # id
        if (empty($module['id'])) {
            self::notice(__('unkow module id'));
        }
        $xml[] = sprintf('<module id="%s">', html::escapeHTML($module['id']));

        # name
        if (empty($module['oname'])) {
            self::notice(__('unknow module name'));
        }
        $xml[] = sprintf('<name>%s</name>', html::escapeHTML($module['name']));

        # version
        if (empty($module['version'])) {
            self::notice(__('unknow module version'));
        }
        $xml[] = sprintf('<version>%s</version>', html::escapeHTML($module['version']));

        # author
        if (empty($module['author'])) {
            self::notice(__('unknow module author'));

        }
        $xml[] = sprintf('<author>%s</author>', html::escapeHTML($module['author']));

        # desc
        if (empty($module['desc'])) {
            self::notice(__('unknow module description'));
        }
        $xml[] = sprintf('<desc>%s</desc>', html::escapeHTML($module['desc']));

        # repository
        if (empty($module['repository'])) {
            self::notice(__('no repository set in _define.php'));
        }

        # file
        $file_pattern = self::parseFilePattern($module, $file_pattern);
        if (empty($file_pattern)) {
            self::notice(__('no zip file pattern set in configuration'));
        }
        $xml[] = sprintf('<file>%s</file>', html::escapeHTML($file_pattern));

        # da dc_min or requires core
        if (!empty($module['requires']) && is_array($module['requires'])) {
            foreach ($module['requires'] as $req) {
                if (!is_array($req)) {
                    $req = [$req];
                }
                if ($req[0] == 'core') {
                    $module['dc_min'] = $req[1];
                    break;
                }
            }
        }
        if (empty($module['dc_min'])) {
            self::notice(__('no minimum dotclear version'), false);
        } else {
            $xml[] = sprintf('<da:dcmin>%s</da:dcmin>', html::escapeHTML($module['dc_min']));
        }

        # da details
        if (empty($module['details'])) {
            self::notice(__('no details URL'), false);
        } else {
            $xml[] = sprintf('<da:details>%s</da:details>', html::escapeHTML($module['details']));
        }

        # da sshot
        //$xml[] = sprintf('<da:sshot>%s</da:sshot>', html::escapeHTML($module['sshot']));

        # da section
        //$xml[] = sprintf('<da:section>%s</da:section>', html::escapeHTML($module['section']));

        # da support
        if (empty($module['support'])) {
            self::notice(__('no support URL'), false);
        } else {
            $xml[] = sprintf('<da:support>%s</da:support>', html::escapeHTML($module['support']));
        }

        # da tags
        //$xml[] = sprintf('<da:tags>%s</da:tags>', html::escapeHTML($module['tags']));

        $xml[] = '</module>';
        $xml[] = '</modules>';

        return implode("\n", $xml);
    }

    private static function parseFilePattern($module, $file_pattern)
    {
        return text::tidyURL(str_replace(
            [
                '%type%',
                '%id%',
                '%version%',
                '%author%'
            ],
            [
                $module['type'],
                $module['id'],
                $module['version'],
                $module['author']
            ],
            $file_pattern
        ));
    }
}