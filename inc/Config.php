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

/* dotclear */
use dcCore;
use dcPage;

/* clearbricks */
use form;

/* php */
use Exception;

/**
 * Admin Improve configuration class
 *
 * Set preference for this plugin.
 */
class Config
{
    private static $init = false;

    public static function init(): bool
    {
        if (defined('DC_CONTEXT_ADMIN')) {
            dcPage::checkSuper();
            self::$init = true;
        }

        return self::$init;
    }

    public static function process(): void
    {
        if (!self::$init) {
            return;
        }

        if (empty($_POST['save'])) {
            return;
        }

        try {
            $pdisabled = '';
            if (!empty($_POST['disabled']) && is_array($_POST['disabled'])) {
                $pdisabled = implode(';', $_POST['disabled']);
            }
            dcCore::app()->blog->settings->get(Core::id())->put('disabled', $pdisabled);
            dcCore::app()->blog->settings->get(Core::id())->put('nodetails', !empty($_POST['nodetails']));

            dcPage::addSuccessNotice(__('Configuration successfully updated'));

            dcCore::app()->adminurl->redirect(
                'admin.plugins',
                ['module' => 'improve', 'conf' => 1, 'chk' => 1, 'redir' => dcCore::app()->admin->__get('list')->getRedir()]
            );
        } catch (Exception $e) {
            dcCore::app()->error->add($e->getMessage());
        }
    }

    public static function render()
    {
        if (!self::$init) {
            return;
        }

        $improve = new Core();

        $modules = [];
        foreach ($improve->modules() as $action) {
            $modules[$action->name()] = $action->id();
        }
        $modules = array_merge($modules, array_flip($improve->disabled()));

        echo '<div class="fieldset"><h4>' . __('List of disabled actions:') . '</h4>';

        foreach ($modules as $name => $id) {
            echo
            '<p><label class="classic" title="' . $id . '">' .
            form::checkbox(['disabled[]'], $id, ['checked' => array_key_exists($id, $improve->disabled())]) .
            __($name) . '</label></p>';
        }
        echo
        '</div><div class="fieldset"><h4>' . __('Options') . '</h4>' .
        '<p><label class="classic">' .
        form::checkbox('nodetails', '1', ['checked' => dcCore::app()->blog->settings->get(Core::id())->get('nodetails')]) .
        __('Hide details of rendered actions') . '</label></p>' .
        '</div>';
    }
}
