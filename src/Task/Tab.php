<?php

declare(strict_types=1);

namespace Dotclear\Plugin\improve\Task;

use Dotclear\Plugin\improve\{
    Task,
    TaskDescriptor
};

/**
 * @brief       improve task: tab class.
 * @ingroup     improve
 *
 * @author      Jean-Christian Denis
 * @copyright   GPL-2.0 https://www.gnu.org/licenses/gpl-2.0.html
 */
class Tab extends Task
{
    protected function getProperties(): TaskDescriptor
    {
        return new TaskDescriptor(
            id: 'tab',
            name: __('Tabulations'),
            description: __('Replace tabulation by four space in php files'),
            configurator: false,
            types: ['plugin', 'theme'],
            priority: 820
        );
    }

    protected function init(): bool
    {
        return true;
    }

    public function readFile(&$content): ?bool
    {
        if (!in_array($this->path_extension, ['php', 'md'])) {
            return null;
        }
        $clean = preg_replace('/(\t)/', '    ', $content);// . "\n";
        if ($content != $clean) {
            $this->success->add(__('Replace tabulation by spaces'));
            $content = $clean;
        }

        return true;
    }

    public function isConfigured(): bool
    {
        return true;
    }
}
