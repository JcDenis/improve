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

class ImproveActionLicensefile extends ImproveAction
{
    protected static $license_filenames = [
        'license',
        'license.md',
        'license.txt'
    ];
    private $action_version = [];
    private $action_full = [];
    private $stop_scan = false;

    protected function init(): bool
    {
        $this->setProperties([
            'id'       => 'license',
            'name'     => __('Fix license file'),
            'desc'     => __('Add or remove full license file to module root'),
            'priority' => 330,
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
            __('Add file and remove others')    => 'full',
            __('Remove license files')          => 'remove'
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
                'action_full'     => !empty($_POST['action_full']) ? $_POST['action_full'] : ''
            ]);
            $this->redirect($url);
        }

        return '
        <p class="field"><label for="action_version">' . __('License version:') . '</label>' .
        form::combo('action_version', $this->action_version, $this->getPreference('action_version')) . '
        </p>

        <p class="field"><label for="action_full">' . __('Action on file:') . '</label>' .
        form::combo('action_full', $this->action_full, $this->getPreference('action_full')) . 
        '</p>';
    }

    public function openModule(string $module_type, array $module_info): ?bool
    {
        $this->type = $module_type;
        $this->module = $module_info;

        if (in_array($this->getPreference('action_full'), ['remove', 'full','overwrite'])) {
            $this->deleteFullLicense(($this->getPreference('action_full') == 'overwrite'));
        }
        if (in_array($this->getPreference('action_full'), ['create', 'overwrite', 'full'])) {
            if (empty($this->getPreference('action_version'))) {
                self::notice(__('no full license type selected'), false);
            } else {
                $this->writeFullLicense();
            }
        }
        return null;
    }

    private function writeFullLicense()
    {
        try {
            $full = file_get_contents(dirname(__FILE__) . '/license/' . $this->getPreference('action_version') . '.full.txt');
            if (empty($full)) {
                self::notice(__('failed to load full license'));

                return null;
            }
            files::putContent($this->module['root'] . '/LICENSE', str_replace("\r\n","\n",$full));
        } catch (Exception $e) {
            self::notice(__('failed to write full license'));

            return null;
        }
        return true;
    }

    private function deleteFullLicense($only_one = false)
    {
        foreach(self::fileExists($this->module['root']) as $file) {
            if ($only_one && $file != 'license') {
                continue;
            }
            if (!files::isDeletable($this->module['root'] . '/' . $file)) {
                self::notice(sprintf(__('full license is not deletable (%s)'), $file), false);
            }
            if (!@unlink($this->module['root'] . '/' . $file)) {
                self::notice(sprintf(__('failed to delete full license (%s)'), $file), false);
            }
        }
        return true;
    }

    private static function fileExists($root)
    {
        $existing = [];
        foreach(self::$license_filenames as $file) {
            if (file_exists($root . '/' . strtolower($file))) {
                $existing[] = strtolower($file);
            }
        }
        return $existing;
    }
}