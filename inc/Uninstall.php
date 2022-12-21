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

namespace Dotclear\Plugin\improve;

class Uninstall
{
    private static $init = false;

    public static function init(): bool
    {
        self::$init = defined('DC_RC_PATH');

        return self::$init;
    }

    public static function process($uninstaller)
    {
        if (!self::$init) {
            return false;
        }

        $uninstaller->addUserAction(
            /* type */
            'settings',
            /* action */
            'delete_all',
            /* ns */
            Core::id(),
            /* desc */
            __('delete all settings')
        );

        $uninstaller->addUserAction(
            /* type */
            'plugins',
            /* action */
            'delete',
            /* ns */
            Core::id(),
            /* desc */
            __('delete plugin files')
        );

        $uninstaller->addUserAction(
            /* type */
            'versions',
            /* action */
            'delete',
            /* ns */
            Core::id(),
            /* desc */
            __('delete the version number')
        );

        $uninstaller->addDirectAction(
            /* type */
            'settings',
            /* action */
            'delete_all',
            /* ns */
            Core::id(),
            /* desc */
            sprintf(__('delete all %s settings'), Core::id())
        );

        $uninstaller->addDirectAction(
            /* type */
            'plugins',
            /* action */
            'delete',
            /* ns */
            Core::id(),
            /* desc */
            sprintf(__('delete %s plugin files'), Core::id())
        );

        $uninstaller->addDirectAction(
            /* type */
            'versions',
            /* action */
            'delete',
            /* ns */
            Core::id(),
            /* desc */
            sprintf(__('delete %s version number'), Core::id())
        );
    }
}
