<?php

declare(strict_types=1);

namespace Dotclear\Plugin\improve\Task;

use Dotclear\Helper\Html\Form\{
    Checkbox,
    Div,
    Fieldset,
    Label,
    Legend,
    Note,
    Para
};
use Dotclear\Plugin\improve\{
    Task,
    TaskDescriptor
};

/**
 * @brief       improve task: EOF class.
 * @ingroup     improve
 *
 * @author      Jean-Christian Denis
 * @copyright   GPL-2.0 https://www.gnu.org/licenses/gpl-2.0.html
 */
class EndOfFile extends Task
{
    protected function getProperties(): TaskDescriptor
    {
        return new TaskDescriptor(
            id: 'endoffile',
            name: __('End of files'),
            description: __('Remove php tag and empty lines from end of files'),
            configurator: true,
            types: ['plugin', 'theme'],
            priority: 860
        );
    }

    protected function init(): bool
    {
        return true;
    }

    public function isConfigured(): bool
    {
        return true;
    }

    public function configure(string $url): string
    {
        if (!empty($_POST['save'])) {
            $this->settings->set('psr2', !empty($_POST['endoffile_psr2']));
            $this->redirect($url);
        }

        return (new Div())->items([
            (new Fieldset())->class('fieldset')->legend((new Legend(__('Contents'))))->fields([
                // endoffile_psr2
                (new Para())->items([
                    (new Checkbox('endoffile_psr2', !empty($this->settings->get('psr2'))))->value(1),
                    (new Label(__('Add a blank line to the end of file'), Label::OUTSIDE_LABEL_AFTER))->for('endoffile_psr2')->class('classic'),
                ]),
                (new Note())->text(__('PSR2 must have a blank line, whereas PSR12 must not.'))->class('form-note'),
            ]),
        ])->render();
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
        ) . ($this->settings->get('psr2') ? "\n" : '');
        if ($content != $clean) {
            $this->success->add(__('Replace end of file'));
            $content = $clean;
        }

        return true;
    }
}
