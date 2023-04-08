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

namespace Dotclear\Plugin\improve\Module;

use Dotclear\Helper\L10n;
use Dotclear\Plugin\improve\Action;

/**
 * Improve action module dcstore.xml
 */
class po2php extends Action
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

    protected function init(): bool
    {
        $this->setProperties([
            'id'          => 'po2php',
            'name'        => __('Translation files'),
            'description' => __('Compile existing translation .po files to fresh .lang.php files'),
            'priority'    => 310,
            'types'       => ['plugin', 'theme'],
        ]);

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
            $this->setSuccess(__('Compile .po file to .lang.php'));
        } else {
            $this->setError(__('Failed to compile .po file'));
        }

        return true;
    }
}
