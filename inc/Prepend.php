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

use Clearbricks;

/**
 * Improve prepend class
 *
 * Manage autoload and some action module helpers.
 */
class prepend
{
    private static $init = false;

    public static function init(): bool
    {
        self::$init = defined('DC_RC_PATH') && defined('DC_CONTEXT_ADMIN');

        return self::$init;
    }

    public static function process()
    {
        if (!self::$init) {
            return false;
        }

        // Core plugin class
        foreach (['Core', 'Action', 'Module'] as $class) {
            Clearbricks::lib()->autoload(['Dotclear\\Plugin\\improve\\' . $class => implode(DIRECTORY_SEPARATOR, [__DIR__, 'core', $class . '.php'])]);
        }

        // Dotclear plugin class
        foreach (['Admin', 'Config', 'Install', 'Manage', 'Prepend', 'Uninstall'] as $class) {
            Clearbricks::lib()->autoload(['Dotclear\\Plugin\\improve\\' . $class => implode(DIRECTORY_SEPARATOR, [__DIR__, $class . '.php'])]);
        }
    }

    public static function getActionsDir(): string
    {
        return __DIR__ . '/module/';
    }

    public static function getActionsNS(): string
    {
        return 'Dotclear\\Plugin\\improve\\Module\\';
    }
}
