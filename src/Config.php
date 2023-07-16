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
use Dotclear\Core\Backend\Notices;
use Dotclear\Core\Process;
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
class Config extends Process
{
    public static function init(): bool
    {
        return self::status(My::checkContext(My::CONFIG));
    }

    public static function process(): bool
    {
        if (!self::status()) {
            return false;
        }

        if (empty($_POST['save'])) {
            return true;
        }

        if (is_null(dcCore::app()->blog) || !My::settings()) {
            return false;
        }

        try {
            $pdisabled = '';
            if (!empty($_POST['disabled']) && is_array($_POST['disabled'])) {
                $pdisabled = implode(';', $_POST['disabled']);
            }
            My::settings()->put('disabled', $pdisabled);
            My::settings()->put('nodetails', !empty($_POST['nodetails']));
            My::settings()->put('allow_distrib', !empty($_POST['allow_distrib']));
            My::settings()->put('combosortby', $_POST['combosortby'] ?: 'name');

            Notices::addSuccessNotice(__('Configuration successfully updated'));

            dcCore::app()->admin->url->redirect(
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
        if (!self::status()) {
            return;
        }

        if (is_null(dcCore::app()->blog)) {
            return;
        }

        $items = [];

        foreach (Improve::instance()->tasks->dump() as $task) {
            $items[] = (new Para())->items([
                (new Checkbox(['disabled[]', 'disabled_' . $task->properties->id], $task->isDisabled()))->value($task->properties->id),
                (new Label($task->properties->id, Label::OUTSIDE_LABEL_AFTER))->class('classic')->for('disabled_' . $task->properties->id),
            ]);
        }

        echo
        (new Div())->items([
            (new Fieldset())->class('fieldset')->legend(new Legend(__('List of disabled tasks')))->fields($items),
            (new Fieldset())->class('fieldset')->legend(new Legend(__('Options')))->fields([
                (new Para())->items([
                    (new Checkbox('nodetails', (bool) My::settings()?->get('nodetails')))->value('1'),
                    (new Label(__('Hide details of rendered tasks'), Label::OUTSIDE_LABEL_AFTER))->class('classic')->for('nodetails'),
                ]),
                (new Para())->items([
                    (new Checkbox('allow_distrib', (bool) My::settings()?->get('allow_distrib')))->value('1'),
                    (new Label(__('Show dotclear distributed modules'), Label::OUTSIDE_LABEL_AFTER))->class('classic')->for('allow_distrib'),
                ]),
                (new Para())->items([
                    (new Label(__('Sort modules seletion by:'), Label::OUTSIDE_LABEL_BEFORE))->for('combosortby'),
                    (new Select('combosortby'))->items([__('Name') => 'name', __('Id') => 'id'])->default(My::settings()?->get('combosortby')),
                ]),
            ]),
        ])->render();
    }
}
