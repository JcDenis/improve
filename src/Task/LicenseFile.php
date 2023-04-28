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
declare(strict_types=1);

namespace Dotclear\Plugin\improve\Task;

use Dotclear\Helper\File\Files;
use Dotclear\Helper\Html\Form\{
    Checkbox,
    Div,
    Fieldset,
    Label,
    Legend,
    Para,
    Select
};
use Dotclear\Plugin\improve\{
    Task,
    TaskDescriptor
};
use Exception;

/**
 * Improve action module license file
 */
class LicenseFile extends Task
{
    /** @var array Possible license filenames */
    protected static $license_filenames = [
        'license',
        'license.md',
        'license.txt',
    ];

    /** @var array Possible license names */
    private $action_version = [];

    /** @var array Action */
    private $action_full = [];

    protected function getProperties(): TaskDescriptor
    {
        return new TaskDescriptor(
            id: 'license',
            name: __('License file'),
            description: __('Add or remove full license file to module root'),
            configurator: true,
            types: ['plugin', 'theme'],
            priority: 330
        );
    }

    protected function init(): bool
    {
        $this->action_version = [
            __('no version selected')                          => '',
            __('gpl2 - GNU General Public License v2')         => 'gpl2',
            __('gpl3 - GNU General Public License v3')         => 'gpl3',
            __('lgpl3 - GNU Lesser General Public License v3') => 'lgpl3',
            __('Massachusetts Institute of Technolog mit')     => 'mit',
            __('Do What The Fuck You Want To Public License')  => 'wtfpl',
        ];
        $this->action_full = [
            __('Do nothing')                    => 0,
            __('Add file if it does not exist') => 'create',
            __('Add file even if it exists')    => 'overwrite',
            __('Add file and remove others')    => 'full',
            __('Remove license files')          => 'remove',
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
            $this->settings->set([
                'action_version' => !empty($_POST['action_version']) ? $_POST['action_version'] : '',
                'action_full'    => !empty($_POST['action_full']) ? $_POST['action_full'] : '',
            ]);
            $this->redirect($url);
        }

        return (new Div())->items([
            (new Fieldset())->class('fieldset')->legend((new Legend(__('Adjustments'))))->fields([
                // action_version
                (new Para())->items([
                    (new Label(__('License version:'), Label::OUTSIDE_LABEL_BEFORE))->for('action_version'),
                    (new Select('action_version'))->default($this->settings->get('action_version'))->items($this->action_version),
                ]),
                // action_full
                (new Para())->items([
                    (new Label(__('Action on file:'), Label::OUTSIDE_LABEL_BEFORE))->for('action_full'),
                    (new Select('action_full'))->default($this->settings->get('action_full'))->items($this->action_full),
                ]),
            ]),
        ])->render();
    }

    public function openModule(): ?bool
    {
        if (in_array($this->settings->get('action_full'), ['remove', 'full','overwrite'])) {
            $this->deleteFullLicense(($this->settings->get('action_full') == 'overwrite'));
        }
        if (in_array($this->settings->get('action_full'), ['create', 'overwrite', 'full'])) {
            if (empty($this->settings->get('action_version'))) {
                $this->warning->add(__('No full license type selected'));
            } else {
                $this->writeFullLicense();
            }
        }

        return null;
    }

    private function writeFullLicense(): ?bool
    {
        try {
            $full = file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . 'licensefile' . DIRECTORY_SEPARATOR . $this->settings->get('action_version') . '.full.txt');
            if (empty($full)) {
                $this->error->add(__('Failed to load license content'));

                return null;
            }
            Files::putContent($this->module->get('root') . DIRECTORY_SEPARATOR . 'LICENSE', str_replace("\r\n", "\n", $full));
            $this->success->add(__('Write new license file "LICENSE"'));
        } catch (Exception $e) {
            $this->error->add(__('Failed to write new license file'));

            return null;
        }

        return true;
    }

    private function deleteFullLicense(bool $only_one = false): bool
    {
        foreach (self::fileExists($this->module->get('root')) as $file) {
            if ($only_one && $file != 'LICENSE') {
                continue;
            }
            if (!Files::isDeletable($this->module->get('root') . DIRECTORY_SEPARATOR . $file)) {
                $this->warning->add(sprintf(__('Old license file is not deletable (%s)'), $file));
            } elseif (!@unlink($this->module->get('root') . DIRECTORY_SEPARATOR . $file)) {
                $this->error->add(sprintf(__('Failed to delete old license file (%s)'), $file));
            } else {
                $this->success->add(sprintf(__('Delete old license file "%s"'), $file));
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
