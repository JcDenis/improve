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

/* dotclear */
use dcCore;

/* php */
use Exception;

/**
 * Improve install class
 *
 * Set default settings and version
 * and manage changes on updates.
 */
class Install
{
    /** @var array Improve default settings */
    private static $default_settings = [[
        'disabled',
        'List of hidden action modules',
        'tab;newline;endoffile',
        'string',
    ]];

    // Nothing to change below
    private static $init = false;

    public static function init(): bool
    {
        self::$init = defined('DC_CONTEXT_ADMIN') && dcCore::app()->newVersion(Core::id(), dcCore::app()->plugins->moduleInfo(Core::id(), 'version'));

        return self::$init;
    }

    public static function process(): ?bool
    {
        if (!self::$init) {
            return false;
        }

        try {
            self::update_0_8_0();
            self::putSettings();

            return true;
        } catch (Exception $e) {
            dcCore::app()->error->add($e->getMessage());

            return false;
        }
    }

    private static function putSettings(): void
    {
        foreach (self::$default_settings as $v) {
            dcCore::app()->blog->settings->get(Core::id())->put(
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
        if (version_compare(dcCore::app()->getVersion(Core::id()) ?? '0', '0.8', '<')) {
            foreach (dcCore::app()->blog->settings->get(Core::id())->dumpGlobalSettings() as $id => $values) {
                $newId = str_replace('ImproveAction', '', $id);
                if ($id != $newId) {
                    dcCore::app()->blog->settings->get(Core::id())->rename($id, strtolower($newId));
                }
            }
        }
    }
}
