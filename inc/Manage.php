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
use dcAdminNotices;
use dcThemes;
use dcUtils;

/* clearbricks */
use html;
use form;

/* php */
use Exception;

/**
 * Improve page class
 *
 * Display page and configure modules
 * and launch action.
 */
class index
{
    /** @var improve $improve  improve core instance */
    private $improve = null;
    /** @var string $type   Current module(s) type */
    private $type = 'plugin';
    /** @var string $module Current module id */
    private $module = '-';
    /** @var action|null $action Current action module */
    private $action = null;

    public function __construct()
    {
        dcPage::checkSuper();

        $this->improve = new improve();
        $this->type    = $this->getType();
        $this->module  = $this->getModule();
        $this->action  = $this->getAction();

        $this->doAction();
        $this->displayPage();
    }

    private function getType(): string
    {
        return $_REQUEST['type'] ?? 'plugin';
    }

    private function getModule(): string
    {
        $module = $_REQUEST['module'] ?? '';
        if (!in_array($module, $this->comboModules())) {
            $module = '-';
        }

        return $module;
    }

    private function getAction(): ?action
    {
        return empty($_REQUEST['config']) ? null : $this->improve->module($_REQUEST['config']);
    }

    private function getPreference(): array
    {
        try {
            if (!empty($this->type)) {
                $preferences = dcCore::app()->blog->settings->improve->preferences;
                if (is_string($preferences)) {
                    $preferences = unserialize($preferences);
                    if (is_array($preferences)) {
                        return array_key_exists($this->type, $preferences) ? $preferences[$this->type] : [];
                    }
                }
            }
        } catch (Exception $e) {
        }

        return [];
    }

    private function setPreferences(): bool
    {
        if (!empty($_POST['save_preferences'])) {
            $preferences[$this->type] = [];
            if (!empty($_POST['actions'])) {
                foreach ($this->improve->modules() as $action) {
                    if (in_array($this->type, $action->types()) && in_array($action->id(), $_POST['actions'])) {
                        $preferences[$this->type][] = $action->id();
                    }
                }
            }
            dcCore::app()->blog->settings->improve->put('preferences', serialize($preferences), 'string', null, true, true);
            dcAdminNotices::addSuccessNotice(__('Configuration successfully updated'));

            return true;
        }

        return false;
    }

    private function comboModules(): array
    {
        $allow_distrib = (bool) dcCore::app()->blog->settings->improve->allow_distrib;
        $official      = [
            'plugin' => explode(',', DC_DISTRIB_PLUGINS),
            'theme'  => explode(',', DC_DISTRIB_THEMES),
        ];

        if (!(dcCore::app()->themes instanceof dcThemes)) {
            dcCore::app()->themes = new dcThemes();
            dcCore::app()->themes->loadModules(dcCore::app()->blog->themes_path, null);
        }

        $combo_modules = [];
        $modules       = self::getModules($this->type == 'plugin' ? 'plugins' : 'themes');
        foreach ($modules as $id => $m) {
            if (!$m['root_writable'] || !$allow_distrib && in_array($id, $official[$this->type])) {
                continue;
            }
            $combo_modules[sprintf(__('%s (%s)'), __($m['name']), $id)] = $id;
        }
        dcUtils::lexicalKeySort($combo_modules);

        return array_merge([__('Select a module') => '-'], $combo_modules);
    }

    public static function getModules(string $type, ?string $id = null): ?array
    {
        $type = $type == 'themes' ? 'themes' : 'plugins';

        $modules = array_merge(dcCore::app()->{$type}->getDisabledModules(), dcCore::app()->{$type}->getModules());

        if (empty($id)) {
            return $modules;
        } elseif (array_key_exists($id, $modules)) {
            return $modules[$id];
        }

        return null;
    }

    private function doAction(): void
    {
        $log_id = '';
        $done   = $this->setPreferences();

        if (!empty($_POST['fix'])) {
            if (empty($_POST['actions'])) {
                dcAdminNotices::addWarningNotice(__('No action selected'));
            } elseif ($this->module == '-') {
                dcAdminNotices::addWarningNotice(__('No module selected'));
            } else {
                try {
                    $time = $this->improve->fixModule(
                        $this->type,
                        $this->module,
                        self::getModules($this->type == 'plugin' ? 'plugins' : 'themes', $this->module),
                        $_POST['actions']
                    );
                    $log_id = $this->improve->writeLogs();
                    dcCore::app()->blog->triggerBlog();

                    if ($this->improve->hasLog('error')) {
                        $notice = ['type' => dcAdminNotices::NOTICE_ERROR, 'msg' => __('Fix of "%s" complete in %s secondes with errors')];
                    } elseif ($this->improve->hasLog('warning')) {
                        $notice = ['type' => dcAdminNotices::NOTICE_WARNING, 'msg' => __('Fix of "%s" complete in %s secondes with warnings')];
                    } elseif ($this->improve->hasLog('success')) {
                        $notice = ['type' => dcAdminNotices::NOTICE_SUCCESS, 'msg' => __('Fix of "%s" complete in %s secondes')];
                    } else {
                        $notice = ['type' => dcAdminNotices::NOTICE_SUCCESS, 'msg' => __('Fix of "%s" complete in %s secondes without messages')];
                    }
                    dcAdminNotices::addNotice($notice['type'], sprintf($notice['msg'], $this->module, $time));

                    $done = true;
                } catch (Exception $e) {
                    dcCore::app()->error->add($e->getMessage());
                    $done = false;
                }
            }
        }

        if ($done) {
            dcCore::app()->adminurl->redirect('admin.plugin.improve', ['type' => $this->type, 'module' => $this->module, 'upd' => $log_id]);
        }
    }

    private function displayPage(): void
    {
        $bc = empty($_REQUEST['config']) ?
            ($this->type == 'theme' ? __('Themes actions') : __('Plugins actions')) :
            __('Configure module');

        echo '<html><head><title>' . __('improve') . '</title>' .
        dcPage::jsLoad(dcPage::getPF('improve/js/index.js')) .
        ($this->action === null ? '' : $this->action->header()) .
        '</head><body>' .
        dcPage::notices() .
        dcPage::breadcrumb([
            __('Plugins') => '',
            __('improve') => '',
            $bc           => '',
        ]);

        if (empty($_REQUEST['config'])) {
            $this->displayActions();
        } else {
            $this->displayConfigurator();
        }

        echo '</body></html>';
    }

    private function displayConfigurator(): void
    {
        $back_url = $_REQUEST['redir'] ?? dcCore::app()->adminurl->get('admin.plugin.improve', ['type' => $this->type]);

        if (null === $this->action) {
            echo '
            <p class="warning">' . __('Unknow module') . '</p>
            <p><a class="back" href="' . $back_url . '">' . __('Back') . '</a></p>';
        } else {
            $redir = $_REQUEST['redir'] ?? dcCore::app()->adminurl->get('admin.plugin.improve', ['type' => $this->type, 'config' => $this->action->id()]);
            $res   = $this->action->configure($redir);

            echo '
            <h3>' . sprintf(__('Configure module "%s"'), $this->action->name()) . '</h3>
            <p><a class="back" href="' . $back_url . '">' . __('Back') . '</a></p>
            <p class="info">' . html::escapeHTML($this->action->description()) . '</p>
            <form action="' . dcCore::app()->adminurl->get('admin.plugin.improve') . '" method="post" id="form-actions">' .
            (empty($res) ? '<p class="message">' . __('Nothing to configure') . '</p>' : $res) . '
            <p class="clear"><input type="submit" name="save" value="' . __('Save') . '" />' .
            form::hidden('type', $this->type) .
            form::hidden('config', $this->action->id()) .
            form::hidden('redir', $redir) .
            dcCore::app()->formNonce() . '</p>' .
            '</form>';
        }
    }

    private function displayActions(): void
    {
        echo
        '<form method="get" action="' . dcCore::app()->adminurl->get('admin.plugin.improve') . '" id="improve_menu">' .
        '<p class="anchor-nav"><label for="type" class="classic">' . __('Goto:') . ' </label>' .
        form::combo('type', [__('Plugins') => 'plugin', __('Themes') => 'theme'], $this->type) . ' ' .
        '<input type="submit" value="' . __('Ok') . '" />' .
        form::hidden('p', 'improve') . '</p>' .
        '</form>';

        $combo_modules = $this->comboModules();
        if (count($combo_modules) == 1) {
            echo '<p class="message">' . __('No module to manage') . '</p>';
        } else {
            echo '<form action="' . dcCore::app()->adminurl->get('admin.plugin.improve') . '" method="post" id="form-actions">' .
            '<table><caption class="hidden">' . __('Actions') . '</caption><thead><tr>' .
            '<th colspan="2" class="first">' . __('Action') . '</td>' .
            '<th scope="col">' . __('Description') . '</td>' .
            '<th scope="col">' . __('Configuration') . '</td>' .
            (DC_DEBUG ? '<th scope="col">' . __('Priority') . '</td>' : '') . /* @phpstan-ignore-line */
            '</tr></thead><tbody>';
            foreach ($this->improve->modules() as $action) {
                if (!in_array($this->type, $action->types())) {
                    continue;
                }
                echo
                '<tr class="line' . ($action->isConfigured() ? '' : ' offline') . '">' .
                '<td class="minimal">' . form::checkbox(
                    ['actions[]',
                        'action_' . $action->id(), ],
                    $action->id(),
                    in_array($action->id(), $this->getPreference()) && $action->isConfigured(),
                    '',
                    '',
                    !$action->isConfigured()
                ) . '</td>' .
                '<td class="minimal nowrap">' .
                    '<label for="action_' . $action->id() . '" class="classic">' . html::escapeHTML($action->name()) . '</label>' .
                '</td>' .
                '<td class="maximal">' . $action->description() . '</td>' .
                '<td class="minimal nowrap modules">' . (
                    false === $action->configurator() ? '' :
                        '<a class="module-config" href="' . dcCore::app()->adminurl->get('admin.plugin.improve', ['type' => $this->type, 'config' => $action->id()]) .
                        '" title="' . sprintf(__("Configure action '%s'"), $action->name()) . '">' . __('Configure') . '</a>'
                ) . '</td>' .
                (DC_DEBUG ? '<td class="minimal"><span class="debug">' . $action->priority() . '</span></td>' : '') . /* @phpstan-ignore-line */
                '</tr>';
            }

            echo '</tbody></table>
            <div class="two-cols">
            <p class="col left"><label for="save_preferences" class="classic">' .
            form::checkbox('save_preferences', 1, !empty($_POST['save_preferences'])) .
            __('Save fields selection as preference') . '</label></p>
            <p class="col right"><label for="module" class="classic">' . __('Select a module:') . ' </label>' .
            form::combo('module', $combo_modules, $this->module) .
            ' <input type="submit" name="fix" value="' . __('Fix it') . '" />' .
            form::hidden(['type'], $this->type) .
            dcCore::app()->formNonce() . '
            </p>
            </div>
            <br class="clear" />
            </form>';

            if (!empty($_REQUEST['upd']) && !dcCore::app()->blog->settings->improve->nodetails) {
                $logs = $this->improve->parseLogs((int) $_REQUEST['upd']);

                if (!empty($logs)) {
                    echo '<div class="fieldset"><h4>' . __('Details') . '</h4>';
                    foreach ($logs as $path => $types) {
                        echo '<h5>' . $path . '</h5>';
                        foreach ($types as $type => $tools) {
                            echo '<div class="' . $type . '"><ul>';
                            foreach ($tools as $tool => $msgs) {
                                $a = $this->improve->module($tool);
                                if (null !== $a) {
                                    echo '<li>' . $a->name() . '<ul>';
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
}

/* process */
new index();
