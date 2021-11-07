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

# Check user perms
dcPage::checkSuper();

$improve = new Improve($core);

$combo_actions = [];
foreach ($improve->modules() as $action) {
    $combo_actions[$action->get('name')] = $action->get('id');
}
$disabled = $improve->disabled();
if (!empty($disabled)) {
    $combo_actions = array_merge($combo_actions, array_flip($disabled));
}

if (!empty($_POST['save'])) {
    try {
        $pdisabled = '';
        if (!empty($_POST['disabled'])) {
            $pdisabled = implode(';', $_POST['disabled']);
        }
        $core->blog->settings->improve->put('disabled', $pdisabled);
        $core->blog->settings->improve->put('nodetails', !empty($_POST['nodetails']));
        dcPage::addSuccessNotice(__('Configuration successfully updated'));

        $core->adminurl->redirect(
            'admin.plugins',
            ['module' => 'improve', 'conf' => 1, 'chk' => 1, 'redir' => $list->getRedir()]
        );
    } catch (Exception $e) {
        $core->error->add($e->getMessage());
    }
}

echo '<div class="fieldset"><h4>' . __('List of disabled actions:') . '</h4>';

foreach ($combo_actions as $name => $id) {
    echo
    '<p><label class="classic" title="' . $id . '">' .
    form::checkbox(['disabled[]'], $id, ['checked' => isset($disabled[$id])]) .
    __($name) . '</label></p>';
}
echo
'</div><div class="fieldset"><h4>' . __('Options') . '</h4>' .
'<p><label class="classic">' .
form::checkbox('nodetails', '1', ['checked' => $core->blog->settings->improve->nodetails]) .
__('Hide details of rendered actions') . '</label></p>' .
'</div>';
