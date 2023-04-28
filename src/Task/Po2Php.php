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

use Dotclear\Helper\L10n;
use Dotclear\Plugin\improve\{
    Task,
    TaskDescriptor
};

/**
 * Improve action module dcstore.xml
 */
class Po2Php extends Task
{
    /** @var string License bloc */
    private $license = <<<EOF
        /**
         * @package Dotclear
         *
         * @copyright Olivier Meunier & Association Dotclear
         * @copyright GPL-2.0-only
         */
        EOF;

    protected function getProperties(): TaskDescriptor
    {
        return new TaskDescriptor(
            id: 'po2php',
            name: __('Translation files'),
            description: __('Compile existing translation .po files to fresh .lang.php files'),
            configurator: false,
            types: ['plugin', 'theme'],
            priority: 310
        );
    }

    protected function init(): bool
    {
        return true;
    }

    public function isConfigured(): bool
    {
        return true;
    }

    public function closeFile(): ?bool
    {
        if (!in_array($this->path_extension, ['po'])) {
            return null;
        }

        if (L10n::generatePhpFileFromPo(substr($this->path_full, 0, -3), $this->license)) {
            $this->success->add(__('Compile .po file to .lang.php'));
        } else {
            $this->error->add(__('Failed to compile .po file'));
        }

        return true;
    }
}
