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
            'name' => __('Store file'),
            'desc' => __('Re-create dcstore.xml file according to _define.php variables'),
            'priority' => 420,
            'config' => true,
            'types' => ['plugin', 'theme']
        ]);

        return true;
    }

    public function isConfigured(): bool
    {
        return !empty($this->getSetting('pattern'));
    }

    public function configure($url): ?string
    {
        if (!empty($_POST['save']) && !empty($_POST['dcstore_pattern'])) {
            $this->setSettings('pattern', (string) $_POST['dcstore_pattern']);
            $this->redirect($url);
        }

        return  
        '<p class="info">' . __('File will be overwritten if it exists') . '</p>' .
        '<p><label class="classic" for="dcstore_pattern">' . 
        __('Predictable URL to zip file on the external repository') . '<br />' .
        form::field('dcstore_pattern', 160, 255, $this->getSetting('pattern')) . '</label>' .
        '</p>' .
        '<p class="form-note">' . 
        sprintf(__('You can use wildcards %s'), '%author%, %type%, %id%, %version%.') . 
        '<br /> ' .
        __('For exemple on github https://github.com/MyGitName/%id%/releases/download/v%version%/%type%-%id%.zip') . 
        '<br />' .
        __('Note on github, you must create a release and join to it the module zip file.') . '
        </p>';
    }

    public function openModule(): ?bool
    {
        $content = $this->generateXML();
        if ($this->hasError()) {
            return false;
        }
        try {
            files::putContent($this->module['sroot'] . '/dcstore.xml', $content);
            $this->setSuccess(__('Write dcstore.xml file.'));
        } catch(Exception $e) {
            $this->setError(__('Failed to write dcstore.xml file'));

            return false;
        }

        return true;
    }

    public function generateXML()
    {
        $xml = ['<modules xmlns:da="http://dotaddict.org/da/">'];

        # id
        if (empty($this->module['id'])) {
            $this->setError(__('unkow module id'));
        }
        $xml[] = sprintf('<module id="%s">', html::escapeHTML($this->module['id']));

        # name
        if (empty($this->module['oname'])) {
            $this->setError(__('unknow module name'));
        }
        $xml[] = sprintf('<name>%s</name>', html::escapeHTML($this->module['name']));

        # version
        if (empty($this->module['version'])) {
            $this->setError(__('unknow module version'));
        }
        $xml[] = sprintf('<version>%s</version>', html::escapeHTML($this->module['version']));

        # author
        if (empty($this->module['author'])) {
            $this->setError(__('unknow module author'));

        }
        $xml[] = sprintf('<author>%s</author>', html::escapeHTML($this->module['author']));

        # desc
        if (empty($this->module['desc'])) {
            $this->setError(__('unknow module description'));
        }
        $xml[] = sprintf('<desc>%s</desc>', html::escapeHTML($this->module['desc']));

        # repository
        if (empty($this->module['repository'])) {
            $this->setError(__('no repository set in _define.php'));
        }

        # file
        $file_pattern = $this->parseFilePattern();
        if (empty($file_pattern)) {
            $this->setError(__('no zip file pattern set in configuration'));
        }
        $xml[] = sprintf('<file>%s</file>', html::escapeHTML($file_pattern));

        # da dc_min or requires core
        if (!empty($this->module['requires']) && is_array($this->module['requires'])) {
            foreach ($this->module['requires'] as $req) {
                if (!is_array($req)) {
                    $req = [$req];
                }
                if ($req[0] == 'core') {
                    $this->module['dc_min'] = $req[1];
                    break;
                }
            }
        }
        if (empty($this->module['dc_min'])) {
            $this->setWarning(__('no minimum dotclear version'));
        } else {
            $xml[] = sprintf('<da:dcmin>%s</da:dcmin>', html::escapeHTML($this->module['dc_min']));
        }

        # da details
        if (empty($this->module['details'])) {
            $this->setWarning(__('no details URL'));
        } else {
            $xml[] = sprintf('<da:details>%s</da:details>', html::escapeHTML($this->module['details']));
        }

        # da sshot
        //$xml[] = sprintf('<da:sshot>%s</da:sshot>', html::escapeHTML($this->module['sshot']));

        # da section
        //$xml[] = sprintf('<da:section>%s</da:section>', html::escapeHTML($this->module['section']));

        # da support
        if (empty($this->module['support'])) {
            $this->setWarning(__('no support URL'));
        } else {
            $xml[] = sprintf('<da:support>%s</da:support>', html::escapeHTML($this->module['support']));
        }

        # da tags
        //$xml[] = sprintf('<da:tags>%s</da:tags>', html::escapeHTML($this->module['tags']));

        $xml[] = '</module>';
        $xml[] = '</modules>';

        return implode("\n", $xml);
    }

    private function parseFilePattern()
    {
        return text::tidyURL(str_replace(
            [
                '%type%',
                '%id%',
                '%version%',
                '%author%'
            ],
            [
                $this->module['type'],
                $this->module['id'],
                $this->module['version'],
                $this->module['author']
            ],
            $this->getSetting('pattern')
        ));
    }
}