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
if (!defined('DC_CONTEXT_ADMIN')) {
    return;
}

dcPage::checkSuper();

$improve = new Improve($core);

$show_filters = false;
$type         = $_REQUEST['type'] ?? 'plugin';

$preferences = @unserialize($core->blog->settings->improve->preferences);
if (!is_array($preferences)) {
    $preferences = [];
}
$preferences = array_merge(['plugin' => [], 'theme' => []], $preferences);

if (!empty($_POST['save_preferences'])) {
    $preferences[$type] = [];
    if (!empty($_POST['actions'])) {
        foreach ($improve->modules() as $action) {
            if (in_array($type, $action->get('types')) && in_array($action->get('id'), $_POST['actions'])) {
                $preferences[$type][] = $action->get('id');
            }
        }
    }
    $core->blog->settings->improve->put('preferences', serialize($preferences), 'string', null, true, true);
    dcPage::addSuccessNotice(__('Configuration successfully updated'));
}

$allow_distrib = (bool) $core->blog->settings->improve->allow_distrib;
$official      = [
    'plugin' => explode(',', DC_DISTRIB_PLUGINS),
    'theme'  => explode(',', DC_DISTRIB_THEMES)
];

if (!isset($core->themes)) {
    $core->themes = new dcThemes($core);
    $core->themes->loadModules($core->blog->themes_path, null);
}

$combo_modules = [];
$modules       = $type == 'plugin' ? $core->plugins->getModules() : $core->themes->getModules();
foreach ($modules as $id => $m) {
    if (!$m['root_writable'] || !$allow_distrib && in_array($id, $official[$type])) {
        continue;
    }
    $combo_modules[__($m['name'])] = $id;
}
dcUtils::lexicalKeySort($combo_modules);
$combo_modules = array_merge([__('Select a module') => '-'], $combo_modules);

$module = $_REQUEST['module'] ?? '';
if (!in_array($module, $combo_modules)) {
    $module = '-';
}

if (!empty($_POST['fix'])) {
    if (empty($_POST['actions'])) {
        dcPage::addWarningNotice(__('No action selected'));
    } elseif ($module == '-') {
        dcPage::addWarningNotice(__('No module selected'));
    } else {
        try {
            $time = $improve->fixModule(
                $type,
                $module,
                $type == 'plugin' ? $core->plugins->getModules($module) : $core->themes->getModules($module),
                $_POST['actions']
            );
            $log_id = $improve->writeLogs();
            $core->blog->triggerBlog();

            if ($improve->hasLog('error')) {
                $notice = ['type' => 'error', 'msg' => __('Fix of "%s" complete in %s secondes with errors')];
            } elseif ($improve->hasLog('warning')) {
                $notice = ['type' => 'warning', 'msg' => __('Fix of "%s" complete in %s secondes with warnings')];
            } elseif ($improve->hasLog('success')) {
                $notice = ['type' => 'success', 'msg' => __('Fix of "%s" complete in %s secondes')];
            } else {
                $notice = ['type' => 'success', 'msg' => __('Fix of "%s" complete in %s secondes without messages')];
            }
            dcPage::addNotice($notice['type'], sprintf($notice['msg'], $module, $time));

            $core->adminurl->redirect('admin.plugin.improve', ['type' => $type, 'module' => $module, 'upd' => $log_id]);
        } catch (Exception $e) {
            $core->error->add($e->getMessage());
        }
    }
}

$action     = null;
$header     = '';
$breadcrumb = [];
if (!empty($_REQUEST['config'])) {
    $breadcrumb = [
        __('Configure module') => ''
    ];
    if (null !== ($action = $improve->module($_REQUEST['config']))) {
        $header = $action->header();
    }
} else {
    $breadcrumb[$type == 'theme' ? __('Themes actions') : __('Plugins actions')] = '';
}

# display header
echo '<html><head><title>' . __('improve') . '</title>' .
dcPage::jsLoad(dcPage::getPF('improve/js/index.js')) .
$header .
'</head><body>' .
dcPage::breadcrumb(array_merge([
    __('Plugins') => '',
    __('improve') => ''
], $breadcrumb)) .
dcPage::notices();

# Menu list
if (empty($_REQUEST['config'])) {
    echo
    '<form method="get" action="' . $core->adminurl->get('admin.plugin.improve') . '" id="improve_menu">' .
    '<p class="anchor-nav"><label for="type" class="classic">' . __('Goto:') . ' </label>' .
    form::combo('type', [__('Plugins') => 'plugin', __('Themes') => 'theme'], $type) . ' ' .
    '<input type="submit" value="' . __('Ok') . '" />' .
    form::hidden('p', 'improve') . '</p>' .
    '</form>';
}

if (!empty($_REQUEST['config'])) {
    $back_url = $_REQUEST['redir'] ?? $core->adminurl->get('admin.plugin.improve', ['type' => $type]);

    if (null !== $action) {
        $redir = $_REQUEST['redir'] ?? $core->adminurl->get('admin.plugin.improve', ['type' => $type, 'config' => $action->get('id')]);
        $res   = $action->configure($redir);

        echo '
        <h3>' . sprintf(__('Configure module "%s"'), $action->get('name')) . '</h3>
        <p><a class="back" href="' . $back_url . '">' . __('Back') . '</a></p>
        <p class="info">' . html::escapeHTML($action->get('desc')) . '</p>
        <form action="' . $core->adminurl->get('admin.plugin.improve') . '" method="post" id="form-actions">' .
        (empty($res) ? '<p class="message">' . __('Nothing to configure') . '</p>' : $res) . '
        <p class="clear"><input type="submit" name="save" value="' . __('Save') . '" />' .
        form::hidden('type', $type) .
        form::hidden('config', $action->get('id')) .
        form::hidden('redir', $redir) .
        $core->formNonce() . '</p>' .
        '</form>';
    } else {
        echo '
        <p class="warning">' . __('Unknow module') . '</p>
        <p><a class="back" href="' . $back_url . '">' . __('Back') . '</a></p>';
    }
} else {
    if (count($combo_modules) == 1) {
        echo '<p class="message">' . __('No module to manage') . '</p>';
    } else {
        echo '<form action="' . $core->adminurl->get('admin.plugin.improve') . '" method="post" id="form-actions">' .
        '<table><caption class="hidden">' . __('Actions') . '</caption><thead><tr>' .
        '<th colspan="2" class="first">' . __('Action') . '</td>' .
        '<th scope="col">' . __('Description') . '</td>' .
        '<th scope="col">' . __('Configuration') . '</td>' .
        (DC_DEBUG ? '<th scope="col">' . __('Priority') . '</td>' : '') .
        '</tr></thead><tbody>';
        foreach ($improve->modules() as $action) {
            if (!in_array($type, $action->get('types'))) {
                continue;
            }
            echo
            '<tr class="line' . ($action->isConfigured() ? '' : ' offline') . '">' .
            '<td class="minimal">' . form::checkbox(
                ['actions[]',
                    'action_' . $action->get('id')],
                $action->get('id'),
                in_array($action->get('id'), $preferences[$type]) && $action->isConfigured(),
                '',
                '',
                !$action->isConfigured()
            ) . '</td>' .
            '<td class="minimal nowrap">' .
                '<label for="action_' . $action->get('id') . '" class="classic">' . html::escapeHTML($action->get('name')) . '</label>' .
            '</td>' .
            '<td class="maximal">' . $action->get('desc') . '</td>' .
            '<td class="minimal nowrap modules">' . (
                false === $action->get('config') ? '' :
                '<a class="module-config" href="' .
                (true === $action->get('config') ? $core->adminurl->get('admin.plugin.improve', ['type' => $type, 'config' => $action->get('id')]) : $action->get('config')) .
                '" title="' . sprintf(__("Configure action '%s'"), $action->get('name')) . '">' . __('Configure') . '</a>'
            ) . '</td>' .
            (DC_DEBUG ? '<td class="minimal"><span class="debug">' . $action->get('priority') . '</span></td>' : '') .
            '</tr>';
        }

        echo '</tbody></table>
        <div class="two-cols">
        <p class="col left"><label for="save_preferences" class="classic">' .
        form::checkbox('save_preferences', 1, !empty($_POST['save_preferences'])) .
        __('Save fields selection as preference') . '</label></p>
        <p class="col right"><label for="module" class="classic">' . __('Select a module:') . ' </label>' .
        form::combo('module', $combo_modules, $module) .
        ' <input type="submit" name="fix" value="' . __('Fix it') . '" />' .
        form::hidden(['type'], $type) .
        $core->formNonce() . '
        </p>
        </div>
        <br class="clear" />
        </form>';

        if (!empty($_REQUEST['upd']) && !$core->blog->settings->improve->nodetails) {
            $logs = $improve->parseLogs($_REQUEST['upd']);

            if (!empty($logs)) {
                echo '<div class="fieldset"><h4>' . __('Details') . '</h4>';
                foreach ($logs as $path => $types) {
                    echo '<h5>' . $path . '</h5>';
                    foreach ($types as $type => $tools) {
                        echo '<div class="' . $type . '"><ul>';
                        foreach ($tools as $tool => $msgs) {
                            $a = $improve->module($tool);
                            echo '<li>' . ($a !== null ? $a->get('name') : 'unknow') . '<ul>';
                            foreach ($msgs as $msg) {
                                echo '<li>' . $msg . '</li>';
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

echo '</body></html>';
