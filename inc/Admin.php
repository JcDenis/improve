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

/* clearbricks */
use Clearbricks;
use files;

/**
 * Improve admin class
 *
 * Add menu and dashboard icons, load Improve action modules.
 */
class Admin
{
    private static $init = false;

    public static function init(): bool
    {
        if (defined('DC_CONTEXT_ADMIN')) {
            dcCore::app()->blog->settings->addNamespace(Core::id());
            self::$init = true;
        }

        return self::$init;
    }

    public static function process()
    {
        if (!self::$init) {
            return false;
        }

        dcCore::app()->addBehavior('adminDashboardFavoritesV2', function (dcFavorites $favs): void {
            $favs->register(
                'improve',
                [
                    'title'      => __('improve'),
                    'url'        => dcCore::app()->adminurl->get('admin.plugin.' . Core::id()),
                    'small-icon' => dcPage::getPF(Core::id() . '/icon.svg'),
                    'large-icon' => dcPage::getPF(Core::id() . '/icon.svg'),
                    //'permissions' => null,
                ]
            );
        });

        dcCore::app()->menu[dcAdmin::MENU_PLUGINS]->addItem(
            __('improve'),
            dcCore::app()->adminurl->get('admin.plugin.' . Core::id()),
            dcPage::getPF(Core::id() . '/icon.svg'),
            preg_match('/' . preg_quote(dcCore::app()->adminurl->get('admin.plugin.' . Core::id())) . '(&.*)?$/', $_SERVER['REQUEST_URI']),
            dcCore::app()->auth->isSuperAdmin()
        );

        foreach (files::scandir(Prepend::getActionsDir()) as $file) {
            if (is_file(Prepend::getActionsDir() . $file) && '.php' == substr($file, -4)) {
                Clearbricks::lib()->autoload([Prepend::getActionsNS() . substr($file, 0, -4) => Prepend::getActionsDir() . $file]);
                dcCore::app()->addBehavior('improveAddAction', [Prepend::getActionsNS() . substr($file, 0, -4), 'create']); /* @phpstan-ignore-line */
            }
        }
    }
}
