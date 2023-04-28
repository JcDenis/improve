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

use dcCore;
use dcPage;
use dcAdminNotices;
use dcThemes;
use dcUtils;
use dcNsProcess;
use Dotclear\Helper\Html\Html;
use Dotclear\Helper\Html\Form\{
    Checkbox,
    Div,
    Form,
    Hidden,
    Label,
    Para,
    Select,
    Submit,
    Text
};
use Exception;

/**
 * Improve page class
 *
 * Display page and configure modules
 * and launch action.
 */
class Manage extends dcNsProcess
{
    /** @var    string  $type   Current module(s) type */
    private static string $type = 'plugin';

    /** @var    string  $module     Current module id */
    private static string $module = '-';

    /** @var    null|Task   $task   Current action module */
    private static ?Task $task = null;

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

        self::$type   = self::getType();
        self::$module = self::getModule();
        self::$task   = self::getTask();

        $log_id = '';
        $done   = self::setPreferences();

        if (!empty($_POST['fix'])) {
            if (empty($_POST['actions'])) {
                dcAdminNotices::addWarningNotice(__('No action selected'));
            } elseif (self::$module == '-') {
                dcAdminNotices::addWarningNotice(__('No module selected'));
            } else {
                try {
                    $time = Core::instance()->fixModule(
                        self::$type == 'plugin' ? dcCore::app()->plugins->getDefine(self::$module) : dcCore::app()->themes->getDefine(self::$module),
                        $_POST['actions']
                    );
                    $log_id = Core::instance()->writeLogs();
                    dcCore::app()->blog?->triggerBlog();

                    if (Core::instance()->hasLog('error')) {
                        $notice = ['type' => dcAdminNotices::NOTICE_ERROR, 'msg' => __('Fix of "%s" complete in %s secondes with errors')];
                    } elseif (Core::instance()->hasLog('warning')) {
                        $notice = ['type' => dcAdminNotices::NOTICE_WARNING, 'msg' => __('Fix of "%s" complete in %s secondes with warnings')];
                    } elseif (Core::instance()->hasLog('success')) {
                        $notice = ['type' => dcAdminNotices::NOTICE_SUCCESS, 'msg' => __('Fix of "%s" complete in %s secondes')];
                    } else {
                        $notice = ['type' => dcAdminNotices::NOTICE_SUCCESS, 'msg' => __('Fix of "%s" complete in %s secondes without messages')];
                    }
                    dcAdminNotices::addNotice($notice['type'], sprintf($notice['msg'], self::$module, $time));

                    $done = true;
                } catch (Exception $e) {
                    dcCore::app()->error->add($e->getMessage());
                    $done = false;
                }
            }
        }

        if ($done) {
            dcCore::app()->adminurl?->redirect('admin.plugin.' . My::id(), ['type' => self::$type, 'module' => self::$module, 'upd' => $log_id]);
        }

        return true;
    }

    public static function render(): void
    {
        if (!static::$init) {
            return;
        }

        dcPage::openModule(
            My::name(),
            dcPage::jsModuleLoad(My::id() . '/js/index.js') .
            (self::$task === null ? '' : self::$task->header())
        );

        echo
        dcPage::breadcrumb([
            __('Plugins')                                                                                                                 => '',
            My::name()                                                                                                                    => '',
            empty($_REQUEST['config']) ? (self::$type == 'theme' ? __('Themes actions') : __('Plugins actions')) : __('Configure module') => '',
        ]) .
        dcPage::notices();

        if (empty($_REQUEST['config'])) {
            self::displayActions();
        } else {
            self::displayConfigurator();
        }

        dcPage::closeModule();
    }

    private static function displayConfigurator(): void
    {
        $back_url = $_REQUEST['redir'] ?? dcCore::app()->adminurl?->get('admin.plugin.' . My::id(), ['type' => self::$type]);

        if (null === self::$task) {
            echo '
            <p class="warning">' . __('Unknow module') . '</p>
            <p><a class="back" href="' . $back_url . '">' . __('Back') . '</a></p>';
        } else {
            $redir = $_REQUEST['redir'] ?? dcCore::app()->adminurl?->get('admin.plugin.' . My::id(), ['type' => self::$type, 'config' => self::$task->properties->id]);
            $res   = self::$task->configure($redir);

            echo '
            <h3>' . sprintf(__('Configure module "%s"'), self::$task->properties->name) . '</h3>
            <p><a class="back" href="' . $back_url . '">' . __('Back') . '</a></p>
            <h4>' . Html::escapeHTML(self::$task->properties->description) . '</h4>' .

            (new Form('form-actions'))->method('post')->action(dcCore::app()->adminurl?->get('admin.plugin.' . My::id()))->fields([
                empty($res) ? (new Text('p', __('Nothing to configure')))->class('message') : (new Text('', $res)),
                (new Para())->class('clear')->items([
                    (new Submit(['save']))->value(__('Save')),
                    (new Hidden('type', self::$type)),
                    (new Hidden('config', self::$task->properties->id)),
                    (new Hidden('redir', $redir)),
                    dcCore::app()->formNonce(false),
                ]),
            ])->render();
        }
    }

    private static function displayActions(): void
    {
        echo
        (new Form('improve_menu'))->method('get')->action(dcCore::app()->adminurl?->get('admin.plugin.' . My::id()))->fields([
            (new Para())->class('anchor-nav')->items([
                (new Label(__('Goto:'), Label::OUTSIDE_LABEL_BEFORE))->for('type')->class('classic'),
                (new Select('type'))->default(self::$type)->items([__('Plugins') => 'plugin', __('Themes') => 'theme']),
                (new Submit('simenu'))->value(__('Save')),
                (new Hidden('p', My::id())),
            ]),
        ])->render();

        $combo_modules = self::comboModules();
        if (count($combo_modules) == 1) {
            echo '<p class="message">' . __('No module to manage') . '</p>';
        } else {
            echo '<p /><form action="' . dcCore::app()->adminurl?->get('admin.plugin.' . My::id()) . '" method="post" id="form-actions">' .
            '<table><caption>' . __('List of available tasks') . '</caption><thead><tr>' .
            '<th colspan="2" class="first">' . __('Task') . '</td>' .
            '<th scope="col">' . __('Description') . '</td>' .
            '<th scope="col">' . __('Configuration') . '</td>' .
            (DC_DEBUG ? '<th scope="col">' . __('Priority') . '</td>' : '') . /* @phpstan-ignore-line */
            '</tr></thead><tbody>';
            foreach (Core::instance()->tasks->dump() as $task) {
                if ($task->isDisabled() || !in_array(self::$type, $task->properties->types)) {
                    continue;
                }
                echo
                '<tr class="line' . ($task->isConfigured() ? '' : ' offline') . '">' .
                '<td class="minimal">' .
                (new Checkbox(
                    ['actions[]', 'action_' . $task->properties->id],
                    in_array($task->properties->id, self::getPreference()) && $task->isConfigured()
                ))->value($task->properties->id)->disabled(!$task->isConfigured())->render() .
                '</td>' .
                '<td class="minimal nowrap">' .
                (new Label(Html::escapeHTML($task->properties->name), Label::OUTSIDE_LABEL_AFTER))->for('action_' . $task->properties->id)->class('classic')->render() .
                '</td>' .
                '<td class="maximal">' . $task->properties->description . '</td>' .
                '<td class="minimal nowrap modules">' . (
                    false === $task->properties->configurator ? '' :
                        '<a class="module-config" href="' . dcCore::app()->adminurl?->get('admin.plugin.' . My::id(), ['type' => self::$type, 'config' => $task->properties->id]) .
                        '" title="' . sprintf(__("Configure action '%s'"), $task->properties->name) . '">' . __('Configure') . '</a>'
                ) . '</td>' .
                (DC_DEBUG ? '<td class="minimal"><span class="debug">' . $task->properties->priority . '</span></td>' : '') . /* @phpstan-ignore-line */
                '</tr>';
            }

            echo '</tbody></table>' .
            (new Div())->class('two-cols')->items([
                (new Para())->class('col left')->items([
                    (new Checkbox('save_preferences', !empty($_POST['save_preferences'])))->value(1),
                    (new Label(__('Save fields selection as preference'), Label::OUTSIDE_LABEL_AFTER))->for('save_preferences')->class('classic'),
                ]),
                (new Para())->class('col right')->items([
                    (new Label(__('Select a module:'), Label::OUTSIDE_LABEL_BEFORE))->for('module')->class('classic'),
                    (new Select('module'))->default(self::$module)->items($combo_modules),
                    (new Submit('fix'))->value(__('Fix it')),
                    (new Hidden(['type'], self::$type)),
                    dcCore::app()->formNonce(false),
                ]),
            ])->render() .
            '<br class="clear" />
            </form>';

            if (!empty($_REQUEST['upd']) && !dcCore::app()->blog?->settings->get(My::id())->get('nodetails')) {
                $logs = Core::instance()->parseLogs((int) $_REQUEST['upd']);

                if (!empty($logs)) {
                    echo '<div class="fieldset"><h4>' . __('Details') . '</h4>';
                    foreach ($logs as $path => $types) {
                        echo '<h5>' . $path . '</h5>';
                        foreach ($types as $type => $tools) {
                            echo '<div class="' . $type . '"><ul>';
                            foreach ($tools as $tool => $msgs) {
                                $a = Core::instance()->tasks->get($tool);
                                if (null !== $a) {
                                    echo '<li>' . $a->properties->name . '<ul>';
                                    foreach ($msgs as $msg) {
                                        echo '<li>' . $msg . '</li>';
                                    }
                                }
                                echo '</ul></li>';
                            }
                            echo '</ul></div>';
                        }
                        echo '';
                    }
                    echo '</div>';
                }
            }
        }
    }

    private static function getType(): string
    {
        return $_REQUEST['type'] ?? 'plugin';
    }

    private static function getModule(): string
    {
        $module = $_REQUEST['module'] ?? '';
        if (!in_array($module, self::comboModules())) {
            $module = '-';
        }

        return $module;
    }

    private static function getTask(): ?Task
    {
        return empty($_REQUEST['config']) ? null : Core::instance()->tasks->get($_REQUEST['config']);
    }

    private static function getPreference(bool $all = false): array
    {
        try {
            if (!empty(self::$type)) {
                $preferences = dcCore::app()->blog?->settings->get(My::id())->get('preferences');
                if (is_string($preferences)) {
                    $preferences = json_decode($preferences, true);
                    if (is_array($preferences)) {
                        return $all ? $preferences : (array_key_exists(self::$type, $preferences) ? $preferences[self::$type] : []);
                    }
                }
            }
        } catch (Exception $e) {
        }

        return [];
    }

    private static function setPreferences(): bool
    {
        if (!empty($_POST['save_preferences']) && !is_null(dcCore::app()->blog)) {
            $preferences              = self::getPreference(true);
            $preferences[self::$type] = [];
            if (!empty($_POST['actions'])) {
                foreach (Core::instance()->tasks->dump() as $task) {
                    if (!$task->isDisabled() && in_array(self::$type, $task->properties->types) && in_array($task->properties->id, $_POST['actions'])) {
                        $preferences[self::$type][] = $task->properties->id;
                    }
                }
            }
            dcCore::app()->blog->settings->get(My::id())->put('preferences', json_encode($preferences), 'string', null, true, true);
            dcAdminNotices::addSuccessNotice(__('Configuration successfully updated'));

            return true;
        }

        return false;
    }

    private static function comboModules(): array
    {
        if (is_null(dcCore::app()->blog)) {
            return [];
        }

        if (!(dcCore::app()->themes instanceof dcThemes)) {
            dcCore::app()->themes = new dcThemes();
            dcCore::app()->themes->loadModules(dcCore::app()->blog->themes_path, null);
        }

        $combo_modules = [];
        $modules       = self::$type == 'plugin' ? dcCore::app()->plugins->getDefines() : dcCore::app()->themes->getDefines();
        if (dcCore::app()->blog->settings->get(My::id())->get('combosortby') == 'id') {
            uasort($modules, fn ($a, $b) => strtolower($a->getId()) <=> strtolower($b->getId()));
        } else {
            uasort($modules, fn ($a, $b) => strtolower(dcUtils::removeDiacritics($a->get('name'))) <=> strtolower(dcUtils::removeDiacritics($b->get('name'))));
        }
        foreach ($modules as $module) {
            if (!$module->get('root_writable') || !dcCore::app()->blog->settings->get(My::id())->get('allow_distrib') && $module->get('distributed')) {
                continue;
            }
            if (dcCore::app()->blog->settings->get(My::id())->get('combosortby') == 'id') {
                $combo_modules[sprintf(__('%s (%s)'), $module->getId(), __($module->get('name')))] = $module->getId();
            } else {
                $combo_modules[sprintf(__('%s (%s)'), __($module->get('name')), $module->getId())] = $module->getId();
            }
        }

        return array_merge([__('Select a module') => '-'], $combo_modules);
    }
}
