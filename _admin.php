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

$core->blog->settings->addNamespace('improve');

$core->addBehavior('adminDashboardFavorites', ['ImproveBehaviors', 'adminDashboardFavorites']);

$core->addBehavior('improveAddAction', ['ImproveActionDcdeprecated', 'create']);
$core->addBehavior('improveAddAction', ['ImproveActionDcstore', 'create']);
$core->addBehavior('improveAddAction', ['ImproveActionEndoffile', 'create']);
$core->addBehavior('improveAddAction', ['ImproveActionGitshields', 'create']);
$core->addBehavior('improveAddAction', ['ImproveActionLicensefile', 'create']);
//$core->addBehavior('improveAddAction', ['ImproveActionLicense', 'create']);
$core->addBehavior('improveAddAction', ['ImproveActionNewline', 'create']);
$core->addBehavior('improveAddAction', ['ImproveActionPhpheader', 'create']);
$core->addBehavior('improveAddAction', ['ImproveActionTab', 'create']);
$core->addBehavior('improveAddAction', ['ImproveActionZip', 'create']);

$_menu['Plugins']->addItem(
    __('improve'),
    $core->adminurl->get('admin.plugin.improve'),
    dcPage::getPF('improve/icon.png'),
    preg_match('/' . preg_quote($core->adminurl->get('admin.plugin.improve')) . '(&.*)?$/', $_SERVER['REQUEST_URI']),
    $core->auth->isSuperAdmin()
);

class ImproveBehaviors
{
    public static function adminDashboardFavorites($core, $favs)
    {
        $favs->register(
            'improve',
            [
                'title'       => __('improve'),
                'url'         => $core->adminurl->get('admin.plugin.improve'),
                'small-icon'  => dcPage::getPF('improve/icon.png'),
                'large-icon'  => dcPage::getPF('improve/icon-b.png'),
                'permissions' => null
            ]
        );
    }
}