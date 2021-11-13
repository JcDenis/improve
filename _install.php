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
    /** @var string Dotclear minimal version */
    private static $dotclear_version = '2.19';
    /** @var array Improve default settings */
    private static $default_settings = [[
        'disabled',
        'List of hidden action modules',
        'tab;newline;endoffile',
        'string',
    ]];

    public static function process(dcCore $core): ?bool
    {
        if (!self::checkModuleVersion($core)) {
            return null;
        }
        if (!self::checkDotclearVersion($core)) {
            throw new Exception(sprintf(
                '%s requires Dotclear %s',
                'improve',
                self::$dotclear_version
            ));
        }

        $core->blog->settings->addNamespace('improve');
        self::update_0_8_0($core);
        self::putSettings($core);
        self::setVersion($core);

        return true;
    }

    private static function getInstalledVersion(dcCore $core): string
    {
        $version = $core->getVersion('improve');

        return is_string($version) ? $version : '0';
    }

    private static function checkModuleVersion(dcCore $core): bool
    {
        return version_compare(
            self::getInstalledVersion($core),
            $core->plugins->moduleInfo('improve', 'version'),
            '<'
        );
    }

    private static function checkDotclearVersion(dcCore $core): bool
    {
        return method_exists('dcUtils', 'versionsCompare')
            && dcUtils::versionsCompare(DC_VERSION, self::$dotclear_version, '>=', false);
    }

    private static function putSettings(dcCore $core): void
    {
        foreach (self::$default_settings as $v) {
            $core->blog->settings->improve->put(
                $v[0],
                $v[2],
                $v[3],
                $v[1],
                false,
                true
            );
        }
    }

    private static function setVersion(dcCore $core): void
    {
        $core->setVersion('improve', $core->plugins->moduleInfo('improve', 'version'));
    }

    /** Update improve < 0.8 : action modules settings name */
    private static function update_0_8_0(dcCore $core): void
    {
        if (version_compare(self::getInstalledVersion($core), '0.8', '<')) {
            foreach ($core->blog->settings->improve->dumpGlobalSettings() as $id => $values) {
                $newId = str_replace('ImproveAction', '', $id);
                if ($id != $newId) {
                    $core->blog->settings->improve->rename($id, strtolower($newId));
                }
            }
        }
    }
}

/* process */
try {
    return install::process($core);
} catch (Exception $e) {
    $core->error->add($e->getMessage());

    return false;
}
