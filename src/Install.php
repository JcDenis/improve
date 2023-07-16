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

use dcCore;
use dcNamespace;
use Dotclear\Core\Process;
use Exception;

/**
 * Improve install class
 *
 * Set default settings and version
 * and manage changes on updates.
 */
class Install extends Process
{
    /** @var array Improve default settings */
    private static $default_settings = [[
        'disabled',
        'List of hidden tasks modules',
        'cssheader;tab;newline;endoffile',
        'string',
    ]];

    public static function init(): bool
    {
        return self::status(My::checkContext(My::INSTALL));
    }

    public static function process(): bool
    {
        if (!self::status()) {
            return false;
        }

        try {
            self::update_0_8_0();
            self::update_1_1_0();
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
            My::settings()?->put(
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
        if (!is_null(dcCore::app()->blog) && My::settings() && version_compare(dcCore::app()->getVersion(My::id()) ?? '0', '0.8', '<')) {
            foreach (My::settings()->dumpGlobalSettings() as $id => $values) {
                $newId = str_replace('ImproveAction', '', $id);
                if ($id != $newId) {
                    My::settings()->rename($id, strtolower($newId));
                }
            }
        }
    }

    /** Update improve < 1.1 : use json_(en|de)code rather than (un)serialize */
    private static function update_1_1_0(): void
    {
        if (version_compare(dcCore::app()->getVersion(My::id()) ?? '0', '1.1', '<')) {
            foreach (['setting_', 'preferences'] as $key) {
                $record = dcCore::app()->con->select(
                    'SELECT * FROM ' . dcCore::app()->prefix . dcNamespace::NS_TABLE_NAME . ' ' .
                    "WHERE setting_ns = '" . dcCore::app()->con->escapeStr(My::id()) . "' " .
                    "AND setting_id LIKE '" . $key . "%' "
                );

                while ($record->fetch()) {
                    try {
                        $value = @unserialize($record->f('setting_value'));
                        $cur   = dcCore::app()->con->openCursor(dcCore::app()->prefix . dcNamespace::NS_TABLE_NAME);
                        $cur->setField('setting_value', json_encode(is_array($value) ? $value : []));
                        $cur->update(
                            "WHERE setting_id = '" . $record->f('setting_id') . "' and setting_ns = '" . dcCore::app()->con->escapeStr($record->f('setting_ns')) . "' " .
                            'AND blog_id ' . (null === $record->f('blog_id') ? 'IS NULL ' : ("= '" . dcCore::app()->con->escapeStr($record->f('blog_id')) . "' "))
                        );
                    } catch(Exception) {
                    }
                }
            }
        }
    }
}
