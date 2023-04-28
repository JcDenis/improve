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

namespace Dotclear\Plugin\improve\Task;

use Dotclear\Plugin\improve\AbstractTask;

/**
 * Improve action module tab
 */
class tab extends AbstractTask
{
    protected function init(): bool
    {
        $this->setProperties([
            'id'          => 'tab',
            'name'        => __('Tabulations'),
            'description' => __('Replace tabulation by four space in php files'),
            'priority'    => 820,
            'types'       => ['plugin', 'theme'],
        ]);

        return true;
    }

    public function readFile(&$content): ?bool
    {
        if (!in_array($this->path_extension, ['php', 'md'])) {
            return null;
        }
        $clean = preg_replace('/(\t)/', '    ', $content);// . "\n";
        if ($content != $clean) {
            $this->setSuccess(__('Replace tabulation by spaces'));
            $content = $clean;
        }

        return true;
    }

    public function isConfigured(): bool
    {
        return true;
    }
}
