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

/**
 * Improve admin class
 *
 * Add menu and dashboard icons, load Improve tasks.
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

            // Add taks to improve
            'improveTaskAdd' => function (Tasks $tasks): void {
                $tasks
                    ->add(new Task\CssHeader())
                    ->add(new Task\DcDeprecated())
                    ->add(new Task\DcStore())
                    ->add(new Task\EndOfFile())
                    ->add(new Task\GitShields())
                    ->add(new Task\LicenseFile())
                    ->add(new Task\NewLine())
                    ->add(new Task\PhpCsFixer())
                    ->add(new Task\PhpHeader())
                    ->add(new Task\PhpStan())
                    ->add(new Task\Po2Php())
                    ->add(new Task\Tab())
                    ->add(new Task\Zip())
                ;
            },
        ]);

        return true;
    }
}
