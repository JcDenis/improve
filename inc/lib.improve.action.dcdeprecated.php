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
class ImproveActionDcdeprecated extends ImproveAction
{
    /** @var array Deprecated functions [filetype [pattern, deprecated, replacement, version]] */
    private static $deprecated = [
        'php' => [
            ['adminPostsActionsCombo', 'adminPostsActionsCombo', 'adminPostsActionsPage'],
            ['adminPostsActionsHeaders', 'adminPostsActionsHeaders', 'adminPostsActionsPage'],
            ['adminPostsActionsContent', 'adminPostsActionsContent', 'adminPostsActionsPage'],
            ['adminCommentsActionsCombo', 'adminCommentsActionsCombo', 'adminCommentsActionsCombo'],
            ['adminCommentsActionsHeaders', 'adminCommentsActionsHeaders', 'adminCommentsActionsPage'],
            ['adminCommentsActionsContent', 'adminCommentsActionsContent', 'adminCommentsActionsPage'],
            ['adminPagesActionsCombo', 'adminPagesActionsCombo', 'adminPagesActionsCombo', 'adminPagesActionsPage'],
            ['adminPagesActionsHeaders', 'adminPagesActionsHeaders', 'adminPagesActionsPage'],
            ['adminPagesActionsContent', 'adminPagesActionsContent', 'adminPagesActionsPage'],

            ['comments_actions.php', 'comments_actions.php', 'dcCommentsActionsPage'],
            ['posts_actions.php', 'posts_actions.php', 'dcPostsActionsPage'],

            ['global_filter', 'global_filter', 'tpl_context::global_filters', '2.11'],

            ['getLangFile', 'getLangFile', 'nothing', 'unknow'],
            ['getTextDirection', 'getTextDirection', 'getLanguageTextDirection'],

            ['dcUtils::jsVar(s|)', 'dcUtils::jsVar(s)', 'dcUtils::jsJson', '2.15'],
            ['adminurl->decode', '$core->adminurl->decode', 'nothing'],
            ['dcPage::help[^B]', 'dcPage::help', 'nothing'],
            ['dcPage::jsVar(s|)', 'dcPage::jsVar(s)', 'dcPage::jsJson', '2.15'],
            ['dcPage::jsLoadIE7', 'dcPage::jsLoadIE7', 'nothing', '2.11'],
            ['dcPage::jsColorPicker', 'dcPage::jsColorPicker', 'nothing', '2.16'],
            ['dcPage::jsToolBar', 'dcPage::jsToolBar', 'nothing'],

            ['adminPostForm[^I]', 'adminPostForm', 'adminPostFormItems'],
            ['adminPostFormSidebar', 'adminPostFormSidebar', 'adminPostFormItems'],

            ['three-cols', 'three-cols', 'three-boxes', '2.6']
        ],
        'js' => [
            ['\sstoreLocalData', 'storeLocalData', 'dotclear.storeLocalData'],
            ['\sdropLocalData', 'dropLocalData', 'dotclear.dropLocalData'],
            ['\sreadLocalData', 'readLocalData', 'dotclear.readLocalData'],
            ['\sgetData', 'getData', 'dotclear.getData'],
            ['\sisObject', 'isObject', 'dotclear.isObject'],
            ['\smergeDeep', 'mergeDeep', 'dotclear.mergeDeep'],
            ['\sgetCookie', 'getCookie', 'dotclear.getCookie'],
            ['\ssetCookie', 'setCookie', 'dotclear.setCookie'],
            ['\sdeleteCookie', 'deleteCookie', 'dotclear.deleteCookie']
        ]
    ];

    protected function init(): bool
    {
        $this->setProperties([
            'id'       => 'dcdeprecated',
            'name'     => __('Dotclear deprecated'),
            'desc'     => __('Search for use of deprecated Dotclear functions'),
            'priority' => 520,
            'types'    => ['plugin', 'theme']
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
                $this->setWarning(sprintf(__('Use of deprecated "%s", you should use "%s" instead.'), $d[1], __($d[2])));
            }
        }

        return true;
    }
}
