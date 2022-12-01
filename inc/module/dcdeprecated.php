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

namespace plugins\improve\module;

/* improve */
use plugins\improve\action;

/**
 * Improve action module Dotclear depreciated
 */
class dcdeprecated extends action
{
    /** @var array Deprecated functions [filetype [pattern, deprecated, replacement, version, help link]] */
    private static $deprecated = [
        'php' => [
            ['(\$core|\$GLOBALS\[(\'|")core(\'|")\]|\$this->core)', '$core', 'dcCore::app()', '2.23', 'https://open-time.net/post/2022/10/21/Adapter-son-code-pour-la-224-n-2'],
            ['(\$_ctx|\$GLOBALS\[(\'|")_ctx(\'|")\])', '$_ctx', 'dcCore::app()->ctx', '2.23', 'https://open-time.net/post/2022/10/22/Adapter-son-code-pour-la-224-n-3'],
            ['(\$_lang|\$GLOBALS\[(\'|")_lang(\'|")\])', '$_lang', 'dcCore::app()->lang', '2.23', 'https://open-time.net/post/2022/10/23/Adapter-son-code-pour-la-224-n-4'],
            ['(\$_menu|\$GLOBALS\[(\'|")_menu(\'|")\])', '$_menu', 'dcCore::app()->menu', '2.23', 'https://open-time.net/post/2022/10/24/Adapter-son-code-pour-la-224-n-5'],
            ['(\$__resources|\$GLOBALS\[(\'|")__resources(\'|")\])', '$__resources', 'dcCore::app()->resources', '2.23', 'https://open-time.net/post/2022/10/26/Adapter-son-code-pour-la-224-n-6'],
            ['(\$__widgets|\$GLOBALS\[(\'|")__widgets(\'|")\])', '$__widgets', 'dcCore::app()->widgets', '2.23', 'https://open-time.net/post/2022/10/31/Adapter-son-code-pour-la-224-n-11'],
            ['(\$_page_number|\$GLOBALS\[(\'|")_page_number(\'|")\])', '$_page_number', 'dcCore::app()->public->getPageNumber()', '2.23', 'https://open-time.net/post/2022/11/01/Adapter-son-code-pour-la-224-n-12'],
            ['(\$_search|\$GLOBALS\[(\'|")_search(\'|")\])', '$_search', 'dcCore::app()->public->search', '2.23', 'https://open-time.net/post/2022/11/02/Adapter-son-code-pour-la-224-n-13'],
            ['(\$_search_count|\$GLOBALS\[(\'|")_search_count(\'|")\])', '$_search_count', 'dcCore::app()->public->search_count', '2.23', 'https://open-time.net/post/2022/11/02/Adapter-son-code-pour-la-224-n-13'],
            ['(\$__theme|\$GLOBALS\[(\'|")__theme(\'|")\])', '$__theme', 'dcCore::app()->public->theme', '2.23', 'https://open-time.net/post/2022/11/03/Adapter-son-code-pour-la-224-n-14'],
            ['(\$__parent_theme|\$GLOBALS\[(\'|")__parent_theme(\'|")\])', '$__parent_theme', 'dcCore::app()->public->parent_theme', '2.23', 'https://open-time.net/post/2022/11/03/Adapter-son-code-pour-la-224-n-14'],
            ['(\$__smilies|\$GLOBALS\[(\'|")__smilies(\'|")\])', '$__smilies', 'dcCore::app()->public->smilies', '2.23', 'https://open-time.net/post/2022/11/04/Adapter-son-code-pour-la-224-n-15'],
            ['(\$__autoload|\$GLOBALS\[(\'|")__autoload(\'|")\])', '$__autoload', 'Clearbricks::lib()->autoload()', '2.23', 'https://open-time.net/post/2022/11/05/Adapter-son-code-pour-la-224-n-16'],
            ['(\$p_url|\$GLOBALS\[(\'|")p_url(\'|")\])', '$p_url', 'dcCore::app()->admin->getPageURL()', '2.23', 'https://open-time.net/post/2022/11/13/Adapter-son-code-pour-la-224-n-24'],

            ['adminPostsActionsPage (\s|"|\')', 'adminPostsActionsPage ', 'adminPostsActions', '2.24', 'https://open-time.net/post/2022/11/17/Adapter-son-code-pour-la-224-n-28'],
            ['adminCommentsActionsPage(\s|"|\')', 'adminCommentsActionsPage', 'adminCommentsActions', '2.24', 'https://open-time.net/post/2022/11/17/Adapter-son-code-pour-la-224-n-28'],
            ['adminPagesActionsPage(\s|"|\')', 'adminPagesActionsPage', 'adminPagesActions', '2.24', 'https://open-time.net/post/2022/11/17/Adapter-son-code-pour-la-224-n-28'],

            ['coreBeforeLoadingNsFiles(\s|"|\')', 'coreBeforeLoadingNsFiles', 'coreBeforeLoadingNsFilesV2', '2.24', 'https://open-time.net/post/2022/11/06/Adapter-son-code-pour-la-224-n-17'],
            ['coreCommentSearch(\s|"|\')', 'coreCommentSearch', 'coreCommentSearchV2', '2.24', 'https://open-time.net/post/2022/11/06/Adapter-son-code-pour-la-224-n-17'],
            ['corePostSearch(\s|"|\')', 'corePostSearch', 'corePostSearchV2', '2.24', 'https://open-time.net/post/2022/11/06/Adapter-son-code-pour-la-224-n-17'],
            ['adminDashboardFavorites(\s|"|\')', 'adminDashboardFavorites', 'adminDashboardFavoritesV2', '2.24', 'https://open-time.net/post/2022/11/06/Adapter-son-code-pour-la-224-n-17'],
            //...

            ['adminPostsActionsCombo', 'adminPostsActionsCombo', 'adminPostsActions', '2.21', ''],
            ['adminPostsActionsHeaders', 'adminPostsActionsHeaders', 'adminPostsActions', '2.21', ''],
            ['adminPostsActionsContent', 'adminPostsActionsContent', 'adminPostsActions', '2.21', ''],
            ['adminCommentsActionsCombo', 'adminCommentsActionsCombo', 'adminCommentsActionsCombo', '2.21', ''],
            ['adminCommentsActionsHeaders', 'adminCommentsActionsHeaders', 'adminCommentsActions', '2.21', ''],
            ['adminCommentsActionsContent', 'adminCommentsActionsContent', 'adminCommentsActions', '2.21', ''],
            ['adminPagesActionsCombo', 'adminPagesActionsCombo', 'adminPagesActionsCombo', 'adminPagesActions', '2.21', ''],
            ['adminPagesActionsHeaders', 'adminPagesActionsHeaders', 'adminPagesActions', '2.21', ''],
            ['adminPagesActionsContent', 'adminPagesActionsContent', 'adminPagesActions', '2.21', ''],

            //
            ['comments_actions.php', 'comments_actions.php', 'dcCommentsActionsPage', '2.21', ''],
            ['posts_actions.php', 'posts_actions.php', 'dcPostsActionsPage', '2.21', ''],

            ['global_filter', 'global_filter', 'tpl_context::global_filters', '2.11', ''],

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
        'js' => [
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

    protected function init(): bool
    {
        $this->setProperties([
            'id'          => 'dcdeprecated',
            'name'        => __('Dotclear deprecated'),
            'description' => __('Search for use of deprecated Dotclear functions'),
            'priority'    => 520,
            'types'       => ['plugin', 'theme'],
        ]);

        return true;
    }

    public function isConfigured(): bool
    {
        return true;
    }

    public function readFile(&$content): ?bool
    {
        if (!in_array($this->path_extension, array_keys(self::$deprecated))) {
            return null;
        }
        foreach (self::$deprecated[$this->path_extension] as $d) {
            if (preg_match('/' . $d[0] . '/i', $content)) {
                $this->setWarning(sprintf(__('Possible use of deprecated "%s", you should use "%s" instead since Dotclear %s.'), $d[1], __($d[2]), $d[3]) . (empty($d[4]) ? '' : ' <a href="' . $d['4'] . '">' . __('Help') . '</a> '));
            }
        }

        return true;
    }
}
