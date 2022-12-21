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

namespace Dotclear\Plugin\improve\Module;

/* improve */
use Dotclear\Plugin\improve\Action;

/* clearbricks */
use form;

/**
 * Improve action module end of file
 */
class endoffile extends Action
{
    protected function init(): bool
    {
        $this->setProperties([
            'id'           => 'endoffile',
            'name'         => __('End of files'),
            'description'  => __('Remove php tag and empty lines from end of files'),
            'priority'     => 860,
            'configurator' => true,
            'types'        => ['plugin', 'theme'],
        ]);

        return true;
    }

    public function isConfigured(): bool
    {
        return true;
    }

    public function configure($url): ?string
    {
        if (!empty($_POST['save'])) {
            $this->setSettings('psr2', !empty($_POST['endoffile_psr2']));
            $this->redirect($url);
        }

        return
        '<p><label class="classic" for="endoffile_psr2">' .
        form::checkbox('endoffile_psr2', 255, $this->getSetting('psr2')) .
        __('Add a blank line to the end of file') .
        '</label></p><p class="form-note">' .
        __('PSR2 must have a blank line, whereas PSR12 must not.') .
        '</p>';
    }

    public function readFile(&$content): ?bool
    {
        if (!in_array($this->path_extension, ['php', 'md'])) {
            return null;
        }
        $clean = preg_replace(
            ['/(\s*)(\?>\s*)$/', '/\n+$/'],
            '',
            $content
        ) . ($this->getSetting('psr2') ? "\n" : '');
        if ($content != $clean) {
            $this->setSuccess(__('Replace end of file'));
            $content = $clean;
        }

        return true;
    }
}
