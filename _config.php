<?php
/**
 * @brief improve, a plugin for Dotclear 2
 *
 * @package Dotclear
 * @subpackage Plugin
 *
 * @author Jean-Christian Denis
 *
 * @copyright Jean-Christian Denis
 * @copyright GPL-2.0 https://www.gnu.org/licenses/gpl-2.0.html
 */
declare(strict_types=1);

if (!defined('DC_CONTEXT_MODULE')) {
    return null;
}

if (Dotclear\Plugin\improve\Config::init()) {
    Dotclear\Plugin\improve\Config::process();
    Dotclear\Plugin\improve\Config::render();
}
