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

$uninstall = implode('\\', ['Dotclear', 'Plugin', basename(__DIR__), 'Uninstall']);

// cope with disabled plugin
if (!class_exists($uninstall)) {
    require implode(DIRECTORY_SEPARATOR, [__DIR__, 'inc', 'Uninstall.php']);
}

if ($uninstall::init()) {
    $uninstall::process($this);
}
