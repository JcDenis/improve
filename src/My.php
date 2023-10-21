<?php

declare(strict_types=1);

namespace Dotclear\Plugin\improve;

use Dotclear\App;
use Dotclear\Module\MyPlugin;

/**
 * @brief       improve My helper.
 * @ingroup     improve
 *
 * @author      Jean-Christian Denis
 * @copyright   GPL-2.0 https://www.gnu.org/licenses/gpl-2.0.html
 */
class My extends MyPlugin
{
    protected static function checkCustomContext(int $context): ?bool
    {
        return match ($context) {
            self::MODULE => App::auth()->isSuperAdmin(),
            default      => null,
        };
    }
}
