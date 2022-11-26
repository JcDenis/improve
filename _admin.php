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
class admin
{
    public static function process(): void
    {
        self::addSettingsNamespace();
        self::addAdminBehaviors();
        self::addAdminMenu();
        self::addImproveActions();
    }

    private static function addSettingsNamespace(): void
    {
        dcCore::app()->blog->settings->addNamespace('improve');
    }

    private static function addAdminBehaviors(): void
    {
        dcCore::app()->addBehavior('adminDashboardFavoritesV2', __NAMESPACE__ . '\admin::adminDashboardFavorites');
    }

    private static function addAdminMenu(): void
    {
        dcCore::app()->menu[dcAdmin::MENU_PLUGINS]->addItem(
            __('improve'),
            dcCore::app()->adminurl->get('admin.plugin.improve'),
            dcPage::getPF('improve/icon.svg'),
            preg_match('/' . preg_quote(dcCore::app()->adminurl->get('admin.plugin.improve')) . '(&.*)?$/', $_SERVER['REQUEST_URI']),
            dcCore::app()->auth->isSuperAdmin()
        );
    }

    private static function addImproveActions(): void
    {
        foreach (files::scandir(prepend::getActionsDir()) as $file) {
            if (is_file(prepend::getActionsDir() . $file) && '.php' == substr($file, -4)) {
                Clearbricks::lib()->autoload([prepend::getActionsNS() . substr($file, 0, -4) => prepend::getActionsDir() . $file]);
                dcCore::app()->addBehavior('improveAddAction', [prepend::getActionsNS() . substr($file, 0, -4), 'create']); /* @phpstan-ignore-line */
            }
        }
    }

    public static function adminDashboardFavorites(dcFavorites $favs): void
    {
        $favs->register(
            'improve',
            [
                'title'       => __('improve'),
                'url'         => dcCore::app()->adminurl->get('admin.plugin.improve'),
                'small-icon'  => dcPage::getPF('improve/icon.svg'),
                'large-icon'  => dcPage::getPF('improve/icon.svg'),
                'permissions' => null,
            ]
        );
    }
}

/* process */
admin::process();
