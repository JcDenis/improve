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

namespace plugins\improve;

if (!defined('DC_RC_PATH') || !defined('DC_CONTEXT_ADMIN')) {
    return;
}

/**
 * Improve prepend class
 *
 * Manage autoload and some action module helpers.
 */
class prepend
{
    public static function process(array &$__autoload): void
    {
        foreach (['improve', 'action', 'module'] as $class) {
            $__autoload['plugins\\improve\\' . $class] = dirname(__FILE__) . '/inc/core/' . $class . '.php';
        }
    }

    public static function getActionsDir(): string
    {
        return dirname(__FILE__) . '/inc/module/';
    }

    public static function getActionsNS(): string
    {
        return 'plugins\\improve\\module\\';
    }
}

/* process */
prepend::process($__autoload);
