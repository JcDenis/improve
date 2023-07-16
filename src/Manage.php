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
use dcThemes;
use Dotclear\Core\Backend\Notices;
use Dotclear\Core\Backend\Page;
use Dotclear\Core\Process;
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
use Dotclear\Helper\Text as TText;
use Exception;

/**
 * Improve page class
 *
 * Display page and configure modules
 * and execute tasks.
 */
class Manage extends Process
{
    /** @var    string  $type   Current module(s) type */
    private static string $type = 'plugin';

    /** @var    string  $module     Current module id */
    private static string $module = '-';

    /** @var    null|Task   $task   Current tasks instance */
    private static ?Task $task = null;

    public static function init(): bool
    {
        return self::status(My::checkContext(My::MANAGE));
    }

    public static function process(): bool
    {
        if (!self::status()) {
            return false;
        }

        self::$type   = self::getType();
        self::$module = self::getModule();
        self::$task   = self::getTask();

        $log_id = '';
        $done   = self::setPreferences();

        if (!empty($_POST['fix'])) {
            if (empty($_POST['actions'])) {
                Notices::addWarningNotice(__('No tasks selected'));
            } elseif (self::$module == '-') {
                Notices::addWarningNotice(__('No module selected'));
            } else {
                try {
                    $time = Improve::instance()->fix(
                        self::$type == 'plugin' ? dcCore::app()->plugins->getDefine(self::$module) : dcCore::app()->themes->getDefine(self::$module),
                        $_POST['actions']
                    );
                    $log_id = Improve::instance()->logs->write();
                    dcCore::app()->blog?->triggerBlog();

                    if (Improve::instance()->logs->has('error')) {
                        $notice = ['type' => Notices::NOTICE_ERROR, 'msg' => __('Fix of "%s" complete in %s secondes with errors')];
                    } elseif (Improve::instance()->logs->has('warning')) {
                        $notice = ['type' => Notices::NOTICE_WARNING, 'msg' => __('Fix of "%s" complete in %s secondes with warnings')];
                    } elseif (Improve::instance()->logs->has('success')) {
                        $notice = ['type' => Notices::NOTICE_SUCCESS, 'msg' => __('Fix of "%s" complete in %s secondes')];
                    } else {
                        $notice = ['type' => Notices::NOTICE_SUCCESS, 'msg' => __('Fix of "%s" complete in %s secondes without messages')];
                    }
                    Notices::addNotice($notice['type'], sprintf($notice['msg'], self::$module, $time));

                    $done = true;
                } catch (Exception $e) {
                    dcCore::app()->error->add($e->getMessage());
                    $done = false;
                }
            }
        }

        if ($done) {
            My::redirect(['type' => self::$type, 'module' => self::$module, 'upd' => $log_id]);
        }

        return true;
    }

    public static function render(): void
    {
        if (!self::status()) {
            return;
        }

        Page::openModule(
            My::name(),
            My::jsLoad('index') .
            (self::$task === null ? '' : self::$task->header())
        );

        echo
        Page::breadcrumb([
            __('Plugins')                                                                                                             => '',
            My::name()                                                                                                                => '',
            empty($_REQUEST['config']) ? (self::$type == 'theme' ? __('Themes tasks') : __('Plugins tasks')) : __('Configure module') => '',
        ]) .
        Notices::getNotices();

        if (empty($_REQUEST['config'])) {
            self::displayActions();
        } else {
            self::displayConfigurator();
        }

        Page::closeModule();
    }

    private static function displayConfigurator(): void
    {
        $back_url = $_REQUEST['redir'] ?? My::manageURL(['type' => self::$type]);

        if (null === self::$task) {
            echo '
            <p class="warning">' . __('Unknow task') . '</p>
            <p><a class="back" href="' . $back_url . '">' . __('Back') . '</a></p>';
        } else {
            $redir = $_REQUEST['redir'] ?? My::manageUrl(['type' => self::$type, 'config' => self::$task->properties->id]);
            $res   = self::$task->configure($redir);

            echo '
            <h3>' . sprintf(__('Configure task "%s"'), self::$task->properties->name) . '</h3>
            <p><a class="back" href="' . $back_url . '">' . __('Back') . '</a></p>
            <h4>' . Html::escapeHTML(self::$task->properties->description) . '</h4>' .

            (new Form('form-actions'))->method('post')->action(My::manageUrl())->fields([
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
        (new Form('improve_menu'))->method('get')->action(My::manageUrl())->fields([
            (new Para())->class('anchor-nav')->items([
                (new Label(__('Goto:'), Label::OUTSIDE_LABEL_BEFORE))->for('type')->class('classic'),
                (new Select('type'))->default(self::$type)->items([__('Plugins') => 'plugin', __('Themes') => 'theme']),
                (new Submit('simenu'))->value(__('Save')),
                (new Hidden('p', My::id())),
                (new Hidden('process', 'Plugin')),
            ]),
        ])->render();

        $combo_modules = self::comboModules();
        if (count($combo_modules) == 1) {
            echo '<p class="message">' . __('No module to manage') . '</p>';
        } else {
            echo '<p /><form action="' . My::manageUrl() . '" method="post" id="form-actions">' .
            '<table><caption>' . __('List of available tasks') . '</caption><thead><tr>' .
            '<th colspan="2" class="first">' . __('Task') . '</td>' .
            '<th scope="col">' . __('Description') . '</td>' .
            '<th scope="col">' . __('Configuration') . '</td>' .
            (DC_DEBUG ? '<th scope="col">' . __('Priority') . '</td>' : '') . /* @phpstan-ignore-line */
            '</tr></thead><tbody>';
            foreach (Improve::instance()->tasks->dump() as $task) {
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
                        '<a class="module-config" href="' . My::manageUrl(['type' => self::$type, 'config' => $task->properties->id]) .
                        '" title="' . sprintf(__("Configure task '%s'"), $task->properties->name) . '">' . __('Configure') . '</a>'
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

            if (!empty($_REQUEST['upd']) && !My::settings()?->get('nodetails')) {
                $logs = Improve::instance()->logs->parse((int) $_REQUEST['upd']);

                if (!empty($logs)) {
                    echo '<div class="fieldset"><h4>' . __('Details') . '</h4>';
                    foreach ($logs as $path => $types) {
                        echo '<h5>' . $path . '</h5>';
                        foreach ($types as $type => $tools) {
                            echo '<div class="' . $type . '"><ul>';
                            foreach ($tools as $tool => $msgs) {
                                $a = Improve::instance()->tasks->get($tool);
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
        return empty($_REQUEST['config']) ? null : Improve::instance()->tasks->get($_REQUEST['config']);
    }

    private static function getPreference(bool $all = false): array
    {
        try {
            if (!empty(self::$type)) {
                $preferences = My::settings()?->get('preferences');
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
                foreach (Improve::instance()->tasks->dump() as $task) {
                    if (!$task->isDisabled() && in_array(self::$type, $task->properties->types) && in_array($task->properties->id, $_POST['actions'])) {
                        $preferences[self::$type][] = $task->properties->id;
                    }
                }
            }
            My::settings()?->put('preferences', json_encode($preferences), 'string', null, true, true);
            Notices::addSuccessNotice(__('Configuration successfully updated'));

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
        if (My::settings()?->get('combosortby') === 'id') {
            uasort($modules, fn ($a, $b) => strtolower($a->getId()) <=> strtolower($b->getId()));
        } else {
            uasort($modules, fn ($a, $b) => strtolower(TText::removeDiacritics($a->get('name'))) <=> strtolower(TText::removeDiacritics($b->get('name'))));
        }
        foreach ($modules as $module) {
            if (!$module->get('root_writable') || !My::settings()->get('allow_distrib') && $module->get('distributed')) {
                continue;
            }
            if (My::settings()->get('combosortby') === 'id') {
                $combo_modules[sprintf(__('%s (%s)'), $module->getId(), __($module->get('name')))] = $module->getId();
            } else {
                $combo_modules[sprintf(__('%s (%s)'), __($module->get('name')), $module->getId())] = $module->getId();
            }
        }

        return array_merge([__('Select a module') => '-'], $combo_modules);
    }
}
