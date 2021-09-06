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
$type = $_REQUEST['type'] ?? 'plugin';

$preferences = @unserialize($core->blog->settings->improve->preferences);
if (!is_array($preferences)) {
    $preferences = [];
}
$preferences = array_merge(['plugin' => [], 'theme' => []], $preferences);

if (!empty($_POST['save_preferences'])) {
    $preferences[$type] = [];
    if (!empty($_POST['actions'])) {
        foreach($improve->modules() as $action) {
            if (in_array($type, $action->types) && in_array($action->id, $_POST['actions'])) {
                $preferences[$type][] = $action->id;
            }
        }
    }
    $core->blog->settings->improve->put('preferences', serialize($preferences), 'string', null, true, true);
}

$allow_distrib = (boolean) $core->blog->settings->improve->allow_distrib;
$official = [
    'plugin' => explode(',', DC_DISTRIB_PLUGINS), 
    'theme' => explode(',', DC_DISTRIB_THEMES)
];

if (!isset($core->themes)) {
    $core->themes = new dcThemes($core);
    $core->themes->loadModules($core->blog->themes_path, null);
}

$combo_modules = [__('Select a module') => '-'];
$modules = $type == 'plugin' ? $core->plugins->getModules() : $core->themes->getModules();
foreach($modules as $id => $m) {
    if (!$m['root_writable'] || !$allow_distrib && in_array($id, $official[$type])) {
        continue;
    }
    $combo_modules[$m['name']] = $id;
}
$module = $_POST['module'] ?? '';
if (!in_array($module, $combo_modules)) {
    $module = '-';
}

if (!empty($_POST['fix'])) {
    if (empty($_POST['actions'])) {
        dcPage::addWarningNotice(__('No action selected'));
    }
    if ($module == '-') {
        dcPage::addWarningNotice(__('No module selected'));
    } else {
        try {
            $time_start = microtime(true);
            $improve->fix(
                $type, 
                $module, 
                $type == 'plugin' ? $core->plugins->getModules($module) : $core->themes->getModules($module), 
                $_POST['actions']
            );
            $time_end = microtime(true);

            $core->blog->triggerBlog();

            dcPage::addSuccessNotice(sprintf(
                __('Fix of %s complete in %s secondes'), 
                $module, 
                substr($time_end - $time_start, 0, 5)
            ));

            http::redirect($improve->getURL(['type' => $type]));
        } catch (Exception $e) {
            $core->error->add($e->getMessage());
        }
    }
}

$breadcrumb = [];
if (!empty($_REQUEST['config'])) {
    $breadcrumb = [
        ($type == 'plugin' ? __('Plugins') : __('Themes')) => 
            $improve->getURL(['type' => ($type == 'plugin' ? 'plugin' : 'theme')]),
        '<span class="page-title">' . __('Configure module') . '</span>'  => ''
    ];
} else {
    $breadcrumb = [
        '<span class="page-title">' . ($type == 'plugin' ? __('Plugins') : __('Themes')) . '</span>'  => '',
        ($type == 'theme' ? __('Plugins') : __('Themes')) => 
            $improve->getURL(['type' => ($type == 'theme' ? 'plugin' : 'theme')])
    ];
}

# display header
echo '<html><head><title>' . __('improve') . '</title></head><body>' .
dcPage::breadcrumb(array_merge([__('improve') => ''], $breadcrumb),['hl' => false]) .
dcPage::notices();

if (!empty($_REQUEST['config'])) {
    $back_url = $_REQUEST['redir'] ?? $improve->getURL(['type' => $type]);

    if (null !== ($action = $improve->module($_REQUEST['config']))) {
        $redir = $_REQUEST['redir'] ?? $improve->getURL(['type' => $type, 'config' => $action->id]);
        $res = $action->configure($redir);

        echo '
        <h3>' . sprintf(__('Configure module "%s"'), $action->name) . '</h3>
        <p>' . html::escapeHTML($action->desc) . '</p>
        <form action="' . $improve->getURL() . '" method="post" id="form-actions">
        <p><a class="back" href="' . $back_url . '">' . __('Back') . '</a></p>' .
        (empty($res) ? '<p class="message">' . __('Nothing to configure'). '</p>' : $res) . '
        <p class="clear"><input type="submit" name="save" value="' . __('Save') . '" />' .
        form::hidden('type', $type) .
        form::hidden('config', $action->id) .
        form::hidden('redir', $redir) .
        $core->formNonce() . '</p>' .
        '<form>';
    } else {
        echo '
        <p class="warning">' . __('Unknow module') . '</p>
        <p><a class="back" href="' . $back_url . '">' . __('Back') . '</a></p>';
    }

} else {

    echo '<h3>' . ($type == 'plugin' ? __('Plugins') : __('Themes')) . '</h3>';

    if (count($combo_modules) == 1) {
        echo '<p class="message">' . __('No module to manage') . '</p>';
    } else {
        echo '<form action="' . $improve->getURL() . '" method="post" id="form-actions">';
        foreach($improve->modules() as $action) {
            if (!in_array($type, $action->types)) {
                continue;
            }
            $p = DC_DEBUG ? '<span class="debug">' . $action->priority. '</span> ' : '';
            echo 
            '<p class="modules">' . $p . '<label for="action_' . $action->id . '" class="classic">' . 
            form::checkbox(
                ['actions[]', 
                'action_' . $action->id], 
                $action->id, 
                in_array($action->id, $preferences[$type]) && $action->isConfigured(), 
                '', 
                '', 
                !$action->isConfigured()
            ) .
            $action->name . '</label>';

            if (false !== $action->config) {
                echo 
                ' - <a class="module-config" href="' . 
                (true === $action->config ? $improve->getURL(['type' => $type, 'config' => $action->id]) : $action->config) . 
                '" title="' . sprintf(__("Configure action '%s'"), $action->name) . '">' . __('Configure module') . '</a>';
            }
            echo  '</p>';
        }

        echo '
        <div>
        <hr />
        <p><label for="save_preferences" class="classic">' .
        form::checkbox('save_preferences', 1, !empty($_POST['save_preferences'])) .
        __('Save fields selection as preference') .'</label></p>
        <p class="field"><label for="module" class="classic">' . __('Select a module:') . '</label>' .
        form::combo('module', $combo_modules, $module) . '
        </p></p>
        <input type="submit" name="fix" value="' . __('Fix it') . '" />' . 
        form::hidden(['type'], $type) .
        $core->formNonce() . '
        </p>
        </div>
        <br class="clear" />
        </form>';
    }
}

echo '</body></html>';