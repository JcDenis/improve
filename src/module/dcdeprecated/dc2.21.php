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
        ['adminPostsActionsCombo', 'adminPostsActionsCombo', 'adminPostsActions', '2.21', ''],
        ['adminPostsActionsHeaders', 'adminPostsActionsHeaders', 'adminPostsActions', '2.21', ''],
        ['adminPostsActionsContent', 'adminPostsActionsContent', 'adminPostsActions', '2.21', ''],
        ['adminCommentsActionsCombo', 'adminCommentsActionsCombo', 'adminCommentsActions', '2.21', ''],
        ['adminCommentsActionsHeaders', 'adminCommentsActionsHeaders', 'adminCommentsActions', '2.21', ''],
        ['adminCommentsActionsContent', 'adminCommentsActionsContent', 'adminCommentsActions', '2.21', ''],
        ['adminPagesActionsCombo', 'adminPagesActionsCombo', 'adminPagesActionsCombo', 'adminPagesActions', '2.21', ''],
        ['adminPagesActionsHeaders', 'adminPagesActionsHeaders', 'adminPagesActions', '2.21', ''],
        ['adminPagesActionsContent', 'adminPagesActionsContent', 'adminPagesActions', '2.21', ''],

        //
        ['comments_actions.php', 'comments_actions.php', 'dcCommentsActionsPage', '2.21', ''],
        ['posts_actions.php', 'posts_actions.php', 'dcPostsActionsPage', '2.21', ''],

        ['global_filter(\s|"|\')', 'global_filter', 'tpl_context::global_filters', '2.11', ''],

        ['getLangFile', 'getLangFile', 'nothing', '2.11', ''],
        ['getTextDirection', 'getTextDirection', 'getLanguageTextDirection', '2.11', ''],

        ['dcUtils::jsVar(s|)', 'dcUtils::jsVar(s)', 'dcUtils::jsJson', '2.15', ''],
        ['adminurl->decode', '$core->adminurl->decode', 'nothing', '2.15', ''],
        ['dcPage::help[^B]', 'dcPage::help', 'nothing', '2.15', ''],
        ['dcPage::jsVar(s|)', 'dcPage::jsVar(s)', 'dcPage::jsJson', '2.15', ''],
        ['dcPage::jsLoadIE7', 'dcPage::jsLoadIE7', 'nothing', '2.11', ''],
        ['dcPage::jsColorPicker', 'dcPage::jsColorPicker', 'nothing', '2.16', ''],
        ['dcPage::jsToolBar', 'dcPage::jsToolBar', 'nothing', '2.16', ''],

        ['adminPostForm[^I]', 'adminPostForm', 'adminPostFormItems', '2.21', ''],
        ['adminPostFormSidebar', 'adminPostFormSidebar', 'adminPostFormItems', '2.21', ''],

        ['three-cols', 'three-cols', 'three-boxes', '2.6', ''],
    ],
    'js'  => [
        ['\sstoreLocalData', 'storeLocalData', 'dotclear.storeLocalData', '2.21', ''],
        ['\sdropLocalData', 'dropLocalData', 'dotclear.dropLocalData', '2.21', ''],
        ['\sreadLocalData', 'readLocalData', 'dotclear.readLocalData', '2.21', ''],
        ['\sgetData', 'getData', 'dotclear.getData', '2.21', ''],
        ['\sisObject', 'isObject', 'dotclear.isObject', '2.21', ''],
        ['\smergeDeep', 'mergeDeep', 'dotclear.mergeDeep', '2.21', ''],
        ['\sgetCookie', 'getCookie', 'dotclear.getCookie', '2.21', ''],
        ['\ssetCookie', 'setCookie', 'dotclear.setCookie', '2.21', ''],
        ['\sdeleteCookie', 'deleteCookie', 'dotclear.deleteCookie', '2.21', ''],
    ],
];
