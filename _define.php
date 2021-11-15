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
if (!defined('DC_RC_PATH')) {
    return null;
}

$this->registerModule(
    'improve',
    'Tiny tools to fix things for module devs',
    'Jean-Christian Denis and contributors',
    '0.8.1',
    [
        'requires'    => [['core', '2.19']],
        'permissions' => null,
        'type'        => 'plugin',
        'support'     => 'https://github.com/JcDenis/improve',
        'details'     => 'https://github.com/JcDenis/improve',
        'repository'  => 'https://raw.githubusercontent.com/JcDenis/improve/master/dcstore.xml',
    ]
);
