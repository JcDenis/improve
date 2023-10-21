<?php

declare(strict_types=1);

namespace Dotclear\Plugin\improve;

use Dotclear\App;

/**
 * @brief       improve task descriptor class.
 * @ingroup     improve
 *
 * @author      Jean-Christian Denis
 * @copyright   GPL-2.0 https://www.gnu.org/licenses/gpl-2.0.html
 */
class TaskDescriptor
{
    /**
     * The priority overload settings prefix.
     *
     * @var     string  PREFIX
     */
    public const PREFIX = 'priority_';

    /**
     * The task priority.
     *
     * @var     int     $priority
     */
    public readonly int $priority;

    /**
     * Constructor sets all properties
     *
     * @param   string              $id             The task ID
     * @param   string              $name           The task translated name
     * @param   string              $description    The task short descripton
     * @param   bool                $configurator   The task has configuration form
     * @param   array<int, string>  $types          The task supported modules types
     * @param   int                 $priority   The task default priority
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
        $this->priority = App::blog()->isDefined() && 1 < ($p = (int) App::blog()->settings()->get(My::id())->get(self::PREFIX . $this->id)) ? $p : abs($priority);
    }
}
