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
use dcAdmin;
use dcCore;
use dcPage;
use dcFavorites;
use dcNsProcess;

/* clearbricks */
use Dotclear\Helper\Clearbricks;
use files;

/**
 * Improve admin class
 *
 * Add menu and dashboard icons, load Improve action modules.
 */
class Backend extends dcNsProcess
{
    public static function init(): bool
    {
        if (defined('DC_CONTEXT_ADMIN')) {
            self::$init = dcCore::app()->auth->isSuperAdmin() && version_compare(phpversion(), My::PHP_MIN, '>=');
        }

        return self::$init;
    }

    public static function process(): bool
    {
        if (!self::$init) {
            return false;
        }

        dcCore::app()->addBehavior('adminDashboardFavoritesV2', function (dcFavorites $favs): void {
            $favs->register(
                My::id(),
                [
                    'title'      => My::name(),
                    'url'        => dcCore::app()->adminurl->get('admin.plugin.' . My::id()),
                    'small-icon' => dcPage::getPF(My::id() . '/icon.svg'),
                    'large-icon' => dcPage::getPF(My::id() . '/icon.svg'),
                    //'permissions' => null,
                ]
            );
        });

        dcCore::app()->menu[dcAdmin::MENU_PLUGINS]->addItem(
            My::name(),
            dcCore::app()->adminurl->get('admin.plugin.' . My::id()),
            dcPage::getPF(My::id() . '/icon.svg'),
            preg_match('/' . preg_quote(dcCore::app()->adminurl->get('admin.plugin.' . My::id())) . '(&.*)?$/', $_SERVER['REQUEST_URI']),
            dcCore::app()->auth->isSuperAdmin()
        );

        foreach (files::scandir(Utils::getActionsDir()) as $file) {
            if (is_file(Utils::getActionsDir() . $file) && '.php' == substr($file, -4)) {
                Clearbricks::lib()->autoload([Utils::getActionsNS() . substr($file, 0, -4) => Utils::getActionsDir() . $file]);
                dcCore::app()->addBehavior('improveAddAction', [Utils::getActionsNS() . substr($file, 0, -4), 'create']); /* @phpstan-ignore-line */
            }
        }

        return true;
    }
}
