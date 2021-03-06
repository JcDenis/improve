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
use adminModulesList;
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
class config
{
    /** @var dcCore $core   dcCore instance */
    private $core = null;
    /** @var adminModulesList $list   adminModulesList instance */
    private $list = null;
    /** @var improve $improve  improve core instance */
    private $improve = null;

    public function __construct(dcCore $core, adminModulesList $list)
    {
        dcPage::checkSuper();

        $this->core    = $core;
        $this->list    = $list;
        $this->improve = new improve($core);

        $this->saveConfig();
        $this->displayConfig();
    }

    private function getModules(): array
    {
        $modules = [];
        foreach ($this->improve->modules() as $action) {
            $modules[$action->name()] = $action->id();
        }
        $modules = array_merge($modules, array_flip($this->improve->disabled()));

        return $modules;
    }

    private function saveConfig(): void
    {
        if (empty($_POST['save'])) {
            return;
        }

        try {
            $pdisabled = '';
            if (!empty($_POST['disabled']) && is_array($_POST['disabled'])) {
                $pdisabled = implode(';', $_POST['disabled']);
            }
            $this->core->blog->settings->improve->put('disabled', $pdisabled);
            $this->core->blog->settings->improve->put('nodetails', !empty($_POST['nodetails']));

            dcPage::addSuccessNotice(__('Configuration successfully updated'));

            $this->core->adminurl->redirect(
                'admin.plugins',
                ['module' => 'improve', 'conf' => 1, 'chk' => 1, 'redir' => $this->list->getRedir()]
            );
        } catch (Exception $e) {
            $this->core->error->add($e->getMessage());
        }
    }

    private function displayConfig(): void
    {
        echo '<div class="fieldset"><h4>' . __('List of disabled actions:') . '</h4>';

        foreach ($this->getModules() as $name => $id) {
            echo
            '<p><label class="classic" title="' . $id . '">' .
            form::checkbox(['disabled[]'], $id, ['checked' => array_key_exists($id, $this->improve->disabled())]) .
            __($name) . '</label></p>';
        }
        echo
        '</div><div class="fieldset"><h4>' . __('Options') . '</h4>' .
        '<p><label class="classic">' .
        form::checkbox('nodetails', '1', ['checked' => $this->core->blog->settings->improve->nodetails]) .
        __('Hide details of rendered actions') . '</label></p>' .
        '</div>';
    }
}

/* process */
new config($core, $list);
