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

use dcAdmin;
use dcCore;
use dcPage;
use dcFavorites;
use dcNsProcess;
use Dotclear\App;
use Dotclear\Helper\File\Files;

/**
 * Improve admin class
 *
 * Add menu and dashboard icons, load Improve action modules.
 */
class Backend extends dcNsProcess
{
    public static function init(): bool
    {
        static::$init = defined('DC_CONTEXT_ADMIN')
            && !is_null(dcCore::app()->auth)
            && dcCore::app()->auth->isSuperAdmin()
            && My::phpCompliant();

        return static::$init;
    }

    public static function process(): bool
    {
        if (!static::$init) {
            return false;
        }

        if (is_null(dcCore::app()->auth) || is_null(dcCore::app()->blog) || is_null(dcCore::app()->adminurl)) {
            return false;
        }

        dcCore::app()->menu[dcAdmin::MENU_PLUGINS]->addItem(
            My::name(),
            dcCore::app()->adminurl->get('admin.plugin.' . My::id()),
            dcPage::getPF(My::id() . '/icon.svg'),
            preg_match('/' . preg_quote(dcCore::app()->adminurl->get('admin.plugin.' . My::id())) . '(&.*)?$/', $_SERVER['REQUEST_URI']),
            dcCore::app()->auth->isSuperAdmin()
        );

        dcCore::app()->addBehaviors([
            'adminDashboardFavoritesV2' => function (dcFavorites $favs): void {
                $favs->register(
                    My::id(),
                    [
                        'title'      => My::name(),
                        'url'        => dcCore::app()->adminurl?->get('admin.plugin.' . My::id()),
                        'small-icon' => dcPage::getPF(My::id() . '/icon.svg'),
                        'large-icon' => dcPage::getPF(My::id() . '/icon.svg'),
                        //'permissions' => null,
                    ]
                );
            },

            // Add actions to improve
            'improveTaskAdd' => function (Tasks $actions): void {
                $dir = __DIR__ . DIRECTORY_SEPARATOR . 'Task' . DIRECTORY_SEPARATOR;
                foreach (Files::scandir($dir) as $file) {
                    if (str_ends_with($file, '.php') && is_file($dir . $file)) {
                        $class = __NAMESPACE__ . '\\Task\\' . basename($file, '.php');
                        $actions->add(new $class());
                    }
                }
            },
        ]);

        return true;
    }
}
