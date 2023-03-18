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
use dcNsProcess;

/* clearbricks */
use Dotclear\Helper\Html\Form\{
    Checkbox,
    Div,
    Fieldset,
    Label,
    Legend,
    Para,
    Text
};

/* php */
use Exception;

/**
 * Admin Improve configuration class
 *
 * Set preference for this plugin.
 */
class Config extends dcNsProcess
{
    public static function init(): bool
    {
        if (defined('DC_CONTEXT_ADMIN')) {
            if (version_compare(phpversion(), My::PHP_MIN, '>=')) {
                self::$init = dcCore::app()->auth->isSuperAdmin();
            } else {
                dcCore::app()->error->add(sprintf(__('%s required php >= %s'), My::id(), My::PHP_MIN));
            }
        }
        return self::$init;
    }

    public static function process(): bool
    {
        if (!self::$init) {
            return false;
        }

        if (empty($_POST['save'])) {
            return true;
        }

        try {
            $pdisabled = '';
            if (!empty($_POST['disabled']) && is_array($_POST['disabled'])) {
                $pdisabled = implode(';', $_POST['disabled']);
            }
            dcCore::app()->blog->settings->get(My::id())->put('disabled', $pdisabled);
            dcCore::app()->blog->settings->get(My::id())->put('nodetails', !empty($_POST['nodetails']));

            dcPage::addSuccessNotice(__('Configuration successfully updated'));

            dcCore::app()->adminurl->redirect(
                'admin.plugins',
                ['module' => My::id(), 'conf' => 1, 'chk' => 1, 'redir' => dcCore::app()->admin->__get('list')->getRedir()]
            );
        } catch (Exception $e) {
            dcCore::app()->error->add($e->getMessage());
        }

        return true;
    }

    public static function render(): void
    {
        if (!self::$init) {
            return;
        }

        $improve = new Core();
        $modules = $items = [];

        foreach ($improve->modules() as $action) {
            $modules[$action->name()] = $action->id();
        }

        foreach (array_merge($modules, array_flip($improve->disabled())) as $name => $id) {
            $items[] = (new Para())->items([
                (new Checkbox(['disabled[]', 'disabled_' . $id], array_key_exists($id, $improve->disabled())))->value($id),
                (new Label($id, Label::OUTSIDE_LABEL_AFTER))->class('classic')->for('disabled_' . $id),
            ]);
        }

        echo
        (new Div())->items([
            (new Fieldset())->class('fieldset')->legend(new Legend(__('List of disabled actions')))->fields($items),
            (new Fieldset())->class('fieldset')->legend(new Legend(__('Options')))->fields([
                (new Para())->items([
                    (new Checkbox('nodetails', (bool) dcCore::app()->blog->settings->get(My::id())->get('nodetails')))->value('1'),
                    (new Label(__('Hide details of rendered actions'), Label::OUTSIDE_LABEL_AFTER))->class('classic')->for('nodetails'),
                ]),
            ]),
        ])->render();
    }
}
