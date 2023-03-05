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

/**
 * Improve utils class
 */
class Utils
{
    public static function getActionsDir(): string
    {
        return __DIR__ . '/module/';
    }

    public static function getActionsNS(): string
    {
        return __NAMESPACE__ . '\\Module\\';
    }
}
