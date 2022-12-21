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

if (!defined('DC_CONTEXT_ADMIN')) {
    return;
}

/* dotclear */
use dcCore;
use dcUtils;

/* php */
use Exception;

/**
 * Improve install class
 *
 * Set default settings and version
 * and manage changes on updates.
 */
class install
{
    /** @var array Improve default settings */
    private static $default_settings = [[
        'disabled',
        'List of hidden action modules',
        'tab;newline;endoffile',
        'string',
    ]];

    public static function process(): ?bool
    {
        if (!dcCore::app()->newVersion(
            basename(__DIR__), 
            dcCore::app()->plugins->moduleInfo(basename(__DIR__), 'version')
        )) {
            return null;
        }

        dcCore::app()->blog->settings->addNamespace(basename(__DIR__));
        self::update_0_8_0();
        self::putSettings();

        return true;
    }

    private static function putSettings(): void
    {
        foreach (self::$default_settings as $v) {
            dcCore::app()->blog->settings->__get(basename(__DIR__))->put(
                $v[0],
                $v[2],
                $v[3],
                $v[1],
                false,
                true
            );
        }
    }

    /** Update improve < 0.8 : action modules settings name */
    private static function update_0_8_0(): void
    {
        if (version_compare(dcCore::app()->getVersion(basename(__DIR__)) ?? '0', '0.8', '<')) {
            foreach (dcCore::app()->blog->settings->__get(basename(__DIR__))->dumpGlobalSettings() as $id => $values) {
                $newId = str_replace('ImproveAction', '', $id);
                if ($id != $newId) {
                    dcCore::app()->blog->settings->__get(basename(__DIR__))->rename($id, strtolower($newId));
                }
            }
        }
    }
}

/* process */
try {
    return install::process();
} catch (Exception $e) {
    dcCore::app()->error->add($e->getMessage());

    return false;
}
