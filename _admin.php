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
use dcPage;
use dcFavorites;

/* clearbricks */
use files;

/* php */
use ArrayObject;

/**
 * Improve admin class
 *
 * Add menu and dashboard icons, load Improve action modules.
 */
class admin
{
    public static function process(dcCore $core, ArrayObject $_menu): void
    {
        self::addSettingsNamespace($core);
        self::addAdminBehaviors($core);
        self::addAdminMenu($core, $_menu);
        self::addImproveActions($core);
    }

    private static function addSettingsNamespace(dcCore $core): void
    {
        $core->blog->settings->addNamespace('improve');
    }

    private static function addAdminBehaviors(dcCore $core): void
    {
        $core->addBehavior('adminDashboardFavorites', __NAMESPACE__ . '\admin::adminDashboardFavorites');
    }

    private static function addAdminMenu(dcCore $core, ArrayObject $_menu): void
    {
        $_menu['Plugins']->addItem(
            __('improve'),
            $core->adminurl->get('admin.plugin.improve'),
            dcPage::getPF('improve/icon.png'),
            preg_match('/' . preg_quote($core->adminurl->get('admin.plugin.improve')) . '(&.*)?$/', $_SERVER['REQUEST_URI']),
            $core->auth->isSuperAdmin()
        );
    }

    private static function addImproveActions(dcCore $core): void
    {
        global $__autoload;

        foreach (files::scandir(prepend::getActionsDir()) as $file) {
            if (is_file(prepend::getActionsDir() . $file) && '.php' == substr($file, -4)) {
                $__autoload[prepend::getActionsNS() . substr($file, 0, -4)] = prepend::getActionsDir() . $file;
                $core->addBehavior('improveAddAction', [prepend::getActionsNS() . substr($file, 0, -4), 'create']); /* @phpstan-ignore-line */
            }
        }
    }

    public static function adminDashboardFavorites(dcCore $core, dcFavorites $favs): void
    {
        $favs->register(
            'improve',
            [
                'title'       => __('improve'),
                'url'         => $core->adminurl->get('admin.plugin.improve'),
                'small-icon'  => dcPage::getPF('improve/icon.png'),
                'large-icon'  => dcPage::getPF('improve/icon-b.png'),
                'permissions' => null,
            ]
        );
    }
}

/* process */
admin::process($core, $_menu);
