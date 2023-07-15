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
return [
    'php' => [
        ['dcCore::autoload\(\)', 'dcCore::autoload()', 'Autoloader::Me()', '2.27', 'https://dotclear.watch/Billet/Les-d%C3%A9pr%C3%A9ci%C3%A9s'],
        ['App::autoload\(\)', 'dcCore::autoload()', 'Autoloader::Me()', '2.27', 'https://dotclear.watch/Billet/Les-d%C3%A9pr%C3%A9ci%C3%A9s'],
        ['__error\(\)', '__error()', 'Autoloader::Me()', '2.27', 'https://dotclear.watch/Billet/La-classe-d-erreur'],
        //todo: logout
        ['(self|static)::\$init', 'dcNsProcess::$init', 'Process::status()', '2.27', 'https://dotclear.watch/Billet/La-class-Process'],
        ['dcAdminHelper::addMenuItem\(\)', 'dcAdminHelper::addMenuItem()', 'My::addBackendMenuItem()', '2.27', 'https://dotclear.watch/Billet/Les-classes-de-module-My'],
        ['dcAdmin::MENU_FAVORITES', 'dcAdmin::MENU_FAVORITES', 'Menus::MENU_FAVORITES', '2.27', 'https://dotclear.watch/Billet/Les-d%C3%A9pr%C3%A9ci%C3%A9s'],
        ['dcAdmin::MENU_BLOG', 'dcAdmin::MENU_BLOG', 'Menus::MENU_BLOG', '2.27', 'https://dotclear.watch/Billet/Les-d%C3%A9pr%C3%A9ci%C3%A9s'],
        ['dcAdmin::MENU_SYSTEM', 'dcAdmin::MENU_SYSTEM', 'Menus::MENU_SYSTEM', '2.27', 'https://dotclear.watch/Billet/Les-d%C3%A9pr%C3%A9ci%C3%A9s'],
        ['dcAdmin::MENU_PLUGINS', 'dcAdmin::MENU_PLUGINS', 'Menus::MENU_PLUGINS', '2.27', 'https://dotclear.watch/Billet/Les-d%C3%A9pr%C3%A9ci%C3%A9s'],
        ['dcPage::notices\(\)', 'dcPage::notices()', 'Notices::getNotices()', '2.27', 'https://dotclear.watch/Billet/Les-d%C3%A9pr%C3%A9ci%C3%A9s'],
        ['dcPage::addMessageNotice\(\)', 'dcPage::addMessageNotice()', 'Notices::addMessageNotice()', '2.27', 'https://dotclear.watch/Billet/Les-d%C3%A9pr%C3%A9ci%C3%A9s'],
        ['dcPage::addSuccessNotice\(\)', 'dcPage::addSuccessNotice()', 'Notices::addSuccessNotice()', '2.27', 'https://dotclear.watch/Billet/Les-d%C3%A9pr%C3%A9ci%C3%A9s'],
        ['dcPage::addWarningNotice\(\)', 'dcPage::addWarningNotice()', 'Notices::addWarningNotice()', '2.27', 'https://dotclear.watch/Billet/Les-d%C3%A9pr%C3%A9ci%C3%A9s'],
        ['dcPage::addErrorNotice\(\)', 'dcPage::addErrorNotice()', 'Notices::addErrorNotice()', '2.27', 'https://dotclear.watch/Billet/Les-d%C3%A9pr%C3%A9ci%C3%A9s'],
        ['dcPage::message\(\)', 'dcPage::message()', 'Notices::message()', '2.27', 'https://dotclear.watch/Billet/Les-d%C3%A9pr%C3%A9ci%C3%A9s'],
        ['dcPage::success\(\)', 'dcPage::success()', 'Notices::success()', '2.27', 'https://dotclear.watch/Billet/Les-d%C3%A9pr%C3%A9ci%C3%A9s'],
        ['dcPage::warning\(\)', 'dcPage::warning()', 'Notices::warning()', '2.27', 'https://dotclear.watch/Billet/Les-d%C3%A9pr%C3%A9ci%C3%A9s'],
        ['dcPage::error\(\)', 'dcPage::error()', 'Notices::error() ', '2.27', 'https://dotclear.watch/Billet/Les-d%C3%A9pr%C3%A9ci%C3%A9s'],
        ['dcPage::cssModuleLoad\(', 'dcPage::cssModuleLoad()', 'My::cssLoad()', '2.27', 'https://dotclear.watch/Billet/Les-classes-de-module-My'],
        ['dcPage::jsModuleLoad\(', 'dcPage::jsModuleLoad()', 'My::jsLoad()', '2.27', 'https://dotclear.watch/Billet/Les-classes-de-module-My'],
        ['dcUtils::cssModuleLoad\(', 'dcUtils::cssModuleLoad()', 'My::cssLoad()', '2.27', 'https://dotclear.watch/Billet/Les-classes-de-module-My'],
        ['dcCore::app\(\)->favs', 'dcCore::app()->favs', 'dcCore::app()->admin->favs', '2.27', 'https://dotclear.watch/Billet/Les-d%C3%A9pr%C3%A9ci%C3%A9s'],
        ['dcCore::app\(\)->menu', 'dcCore::app()->menu', 'dcCore::app()->admin->menus', '2.27', 'https://dotclear.watch/Billet/Les-d%C3%A9pr%C3%A9ci%C3%A9s'],
        ['dcCore::app\(\)->adminurl', 'dcCore::app()->adminurl', 'dcCore::app()->admin->url', '2.27', 'https://dotclear.watch/Billet/https://dotclear.watch/Billet/Gestion-d-URL-d-administration'],

        ['adminBlogFilter', 'adminBlogFilter', 'Dotclear\Core\Backend\Filter\FilterBlogs', '2.27', 'https://dotclear.watch/Billet/D%C3%A9placement-de-classes'],
        ['adminBlogList', 'adminBlogList', 'Dotclear\Core\Backend\Listing\ListingBlogs', '2.27', 'https://dotclear.watch/Billet/D%C3%A9placement-de-classes'],
        ['adminCommentFilter', 'adminCommentFilter', 'Dotclear\Core\Backend\Filter\FilterComments', '2.27', 'https://dotclear.watch/Billet/D%C3%A9placement-de-classes'],
        ['adminCommentList', 'adminCommentList', 'Dotclear\Core\Backend\Listing\ListingComments', '2.27', 'https://dotclear.watch/Billet/D%C3%A9placement-de-classes'],
        ['adminGenericFilterV2', 'adminGenericFilterV2', 'Dotclear\Core\Backend\Filter\Filters', '2.27', 'https://dotclear.watch/Billet/D%C3%A9placement-de-classes'],
        ['adminGenericListV2', 'adminGenericListV2', 'Dotclear\Core\Backend\Listing\Listing', '2.27', 'https://dotclear.watch/Billet/D%C3%A9placement-de-classes'],
        ['adminMediaFilter', 'adminMediaFilter', 'Dotclear\Core\Backend\Filter\FilterMedia', '2.27', 'https://dotclear.watch/Billet/D%C3%A9placement-de-classes'],
        ['adminMediaList', 'adminMediaList', 'Dotclear\Core\Backend\Listing\ListingMedia', '2.27', 'https://dotclear.watch/Billet/D%C3%A9placement-de-classes'],
        ['adminMediaPage', 'adminMediaPage', 'Dotclear\Core\Backend\MediaPage', '2.27', 'https://dotclear.watch/Billet/D%C3%A9placement-de-classes'],
        ['adminModulesList', 'adminModulesList', 'Dotclear\Core\Backend\ModulesList', '2.27', 'https://dotclear.watch/Billet/D%C3%A9placement-de-classes'],
        ['adminPostFilter', 'adminPostFilter', 'Dotclear\Core\Backend\Filter\FilterPosts', '2.27', 'https://dotclear.watch/Billet/D%C3%A9placement-de-classes'],
        ['adminPostList', 'adminPostList', 'Dotclear\Core\Backend\Listing\ListingPosts', '2.27', 'https://dotclear.watch/Billet/D%C3%A9placement-de-classes'],
        ['adminPostMiniList', 'adminPostMiniList', 'Dotclear\Core\Backend\Listing\ListingPostsMini', '2.27', 'https://dotclear.watch/Billet/D%C3%A9placement-de-classes'],
        ['adminUserFilter', 'adminUserFilter', 'Dotclear\Core\Backend\Filter\FilterUsers', '2.27', 'https://dotclear.watch/Billet/D%C3%A9placement-de-classes'],
        ['adminUserList', 'adminUserList', 'Dotclear\Core\Backend\Listing\ListingUsers', '2.27', 'https://dotclear.watch/Billet/D%C3%A9placement-de-classes'],
        ['adminThemesList', 'adminThemesList', 'Dotclear\Core\Backend\ThemesList', '2.27', 'https://dotclear.watch/Billet/D%C3%A9placement-de-classes'],
        ['adminUserPref', 'adminUserPref', 'Dotclear\Core\Backend\UserPref', '2.27', 'https://dotclear.watch/Billet/D%C3%A9placement-de-classes'],
        ['dcActions', 'dcActions', 'Dotclear\Core\Backend\Action\Actions', '2.27', 'https://dotclear.watch/Billet/D%C3%A9placement-de-classes'],
        ['dcAdmin', 'dcAdmin', 'Dotclear\Core\Backend\Utility', '2.27', 'https://dotclear.watch/Billet/D%C3%A9placement-de-classes'],
        ['dcAdminBlogPref', 'dcAdminBlogPref', 'Dotclear\Core\Backend\BlogPref', '2.27', 'https://dotclear.watch/Billet/D%C3%A9placement-de-classes'],
        ['dcAdminCombos', 'dcAdminCombos', 'Dotclear\Core\Backend\Combos', '2.27', 'https://dotclear.watch/Billet/D%C3%A9placement-de-classes'],
        ['dcAdminFilter', 'dcAdminFilter', 'Dotclear\Core\Backend\Filter\Filter', '2.27', 'https://dotclear.watch/Billet/D%C3%A9placement-de-classes'],
        ['dcAdminFilters', 'dcAdminFilters', 'Dotclear\Core\Backend\Filter\FiltersLibrary', '2.27', 'https://dotclear.watch/Billet/D%C3%A9placement-de-classes'],
        ['dcAdminHelper', 'dcAdminHelper', 'Dotclear\Core\Backend\Helper', '2.27', 'https://dotclear.watch/Billet/D%C3%A9placement-de-classes'],
        ['dcAdminNotices', 'dcAdminNotices', 'Dotclear\Core\Backend\Notices', '2.27', 'https://dotclear.watch/Billet/D%C3%A9placement-de-classes'],
        ['dcAdminURL', 'dcAdminURL', 'Dotclear\Core\Backend\Url', '2.27', 'https://dotclear.watch/Billet/D%C3%A9placement-de-classes'],
        ['dcBlogsActions', 'dcBlogsActions', 'Dotclear\Core\Backend\Action\ActionsBlogs', '2.27', 'https://dotclear.watch/Billet/D%C3%A9placement-de-classes'],
        ['dcCommentsActions', 'dcCommentsActions', 'Dotclear\Core\Backend\Action\ActionsComments', '2.27', 'https://dotclear.watch/Billet/D%C3%A9placement-de-classes'],
        ['dcPostsActions', 'dcPostsActions', 'Dotclear\Core\Backend\dcPostsActions', '2.27', 'https://dotclear.watch/Billet/D%C3%A9placement-de-classes'],
        ['dcFavorites', 'dcFavorites', 'Dotclear\Core\Backend\Favorites', '2.27', 'https://dotclear.watch/Billet/D%C3%A9placement-de-classes'],
        ['dcMenu', 'dcMenu', 'Dotclear\Core\Backend\Menu', '2.27', 'https://dotclear.watch/Billet/D%C3%A9placement-de-classes'],
        ['dcPage', 'dcPage', 'Dotclear\Core\Backend\Page', '2.27', 'https://dotclear.watch/Billet/D%C3%A9placement-de-classes'],
        ['dcPager', 'dcPager', 'Dotclear\Core\Backend\Listing\Pager', '2.27', 'https://dotclear.watch/Billet/D%C3%A9placement-de-classes'],
        ['dcThemeConfig', 'dcThemeConfig', 'Dotclear\Core\Backend\ThemeConfig', '2.27', 'https://dotclear.watch/Billet/D%C3%A9placement-de-classes'],
        ['dcPublic', 'dcPublic', 'Dotclear\Core\Frontend\Utility', '2.27', 'https://dotclear.watch/Billet/D%C3%A9placement-de-classes'],
        ['dcNsProcess', 'dcNsProcess', 'Dotclear\Core\Process', '2.27', 'https://dotclear.watch/Billet/D%C3%A9placement-de-classes'],
        ['dcUpgrade', 'dcUpgrade', 'Dotclear\Core\Upgrade\Upgrade', '2.27', 'https://dotclear.watch/Billet/D%C3%A9placement-de-classes'],

        ['dcCore::app\(\)->auth === null', 'is_null(dcCore::app()->auth)', 'isset(dcCore::app()->auth)', '2.27'],
        ['is_null\(dcCore::app\(\)->auth\)', 'is_null(dcCore::app()->auth)', 'isset(dcCore::app()->auth)', '2.27'],
    ],
];
