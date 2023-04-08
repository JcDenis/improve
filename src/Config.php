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
use dcNsProcess;
use Dotclear\Helper\Html\Form\{
    Checkbox,
    Div,
    Fieldset,
    Label,
    Legend,
    Para,
    Select,
    Text
};
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
        static::$init = defined('DC_CONTEXT_ADMIN')
            && dcCore::app()->auth->isSuperAdmin()
            && My::phpCompliant();

        return static::$init;
    }

    public static function process(): bool
    {
        if (!static::$init) {
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
            dcCore::app()->blog->settings->get(My::id())->put('allow_distrib', !empty($_POST['allow_distrib']));
            dcCore::app()->blog->settings->get(My::id())->put('combosortby', $_POST['combosortby'] ?: 'name');

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
        if (!static::$init) {
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
                (new Para())->items([
                    (new Checkbox('allow_distrib', (bool) dcCore::app()->blog->settings->get(My::id())->get('allow_distrib')))->value('1'),
                    (new Label(__('Show dotclear distributed modules'), Label::OUTSIDE_LABEL_AFTER))->class('classic')->for('allow_distrib'),
                ]),
                (new Para())->items([
                    (new Label(__('Sort modules seletion by:'), Label::OUTSIDE_LABEL_BEFORE))->for('combosortby'),
                    (new Select('combosortby'))->items([__('Name') => 'name', __('Id') => 'id'])->default(dcCore::app()->blog->settings->get(My::id())->get('combosortby')),
                ]),
            ]),
        ])->render();
    }
}
