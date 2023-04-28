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

/**
 * Task description.
 */
class TaskDescriptor
{
    /** @var    string  The priority overload settings prefix  */
    public const PREFIX = 'priority_';

    /** @var    int     $priority   The task priority */
    public readonly int $priority;

    /**
     * Constructor sets all properties
     *
     * @param   string  $id             The task ID
     * @param   string  $name           The task translated name
     * @param   string  $description    The task short descripton
     * @param   bool    $configurator   The task has configuration form
     * @param   array   $types          The task supported modules types
     * @param   int     $priority   The task default priority
     */
    public function __construct(
        public readonly string $id,
        public readonly string $name,
        public readonly string $description,
        public readonly array $types,
        public readonly bool $configurator,
        int $priority = 500
    ) {
        // Overload task priority from settings
        if (!is_null(dcCore::app()->blog) && 1 < ($p = (int) dcCore::app()->blog?->settings->get(My::id())->get(self::PREFIX . $this->id))) {
            $this->priority = $p;
        } else {
            $this->priority = abs($priority);
        }
    }
}
