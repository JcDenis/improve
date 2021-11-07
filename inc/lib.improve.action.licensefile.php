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
    /** @var array Possible license filenames */
    protected static $license_filenames = [
        'license',
        'license.md',
        'license.txt'
    ];

    /** @var array Possible license names */
    private $action_version = [];

    /** @var array Action */
    private $action_full = [];

    protected function init(): bool
    {
        $this->setProperties([
            'id'       => 'license',
            'name'     => __('License file'),
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
            $this->setSettings([
                'action_version' => !empty($_POST['action_version']) ? $_POST['action_version'] : '',
                'action_full'    => !empty($_POST['action_full']) ? $_POST['action_full'] : ''
            ]);
            $this->redirect($url);
        }

        return '
        <p class="field"><label for="action_version">' . __('License version:') . '</label>' .
        form::combo('action_version', $this->action_version, $this->getSetting('action_version')) . '
        </p>

        <p class="field"><label for="action_full">' . __('Action on file:') . '</label>' .
        form::combo('action_full', $this->action_full, $this->getSetting('action_full')) .
        '</p>';
    }

    public function openModule(): ?bool
    {
        if (in_array($this->getSetting('action_full'), ['remove', 'full','overwrite'])) {
            $this->deleteFullLicense(($this->getSetting('action_full') == 'overwrite'));
        }
        if (in_array($this->getSetting('action_full'), ['create', 'overwrite', 'full'])) {
            if (empty($this->getSetting('action_version'))) {
                $this->setWarning(__('No full license type selected'));
            } else {
                $this->writeFullLicense();
            }
        }

        return null;
    }

    private function writeFullLicense(): ?bool
    {
        try {
            $full = file_get_contents(dirname(__FILE__) . '/license/' . $this->getSetting('action_version') . '.full.txt');
            if (empty($full)) {
                $this->setError(__('Failed to load license content'));

                return null;
            }
            files::putContent($this->module['root'] . '/LICENSE', str_replace("\r\n", "\n", $full));
            $this->setSuccess(__('Write new license file "LICENSE"'));
        } catch (Exception $e) {
            $this->setError(__('Failed to write new license file'));

            return null;
        }

        return true;
    }

    private function deleteFullLicense(bool $only_one = false): bool
    {
        foreach (self::fileExists($this->module['root']) as $file) {
            if ($only_one && $file != 'LICENSE') {
                continue;
            }
            if (!files::isDeletable($this->module['root'] . '/' . $file)) {
                $this->setWarning(sprintf(__('Old license file is not deletable (%s)'), $file));
            } elseif (!@unlink($this->module['root'] . '/' . $file)) {
                $this->setError(sprintf(__('Failed to delete old license file (%s)'), $file));
            } else {
                $this->setSuccess(sprintf(__('Delete old license file "%s"'), $file));
            }
        }

        return true;
    }

    private static function fileExists(string $root): array
    {
        $existing = [];
        foreach (self::$license_filenames as $file) {
            if (file_exists($root . '/' . strtolower($file))) {
                $existing[] = strtolower($file);
            }
        }

        return $existing;
    }
}
