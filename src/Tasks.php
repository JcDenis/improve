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
 * The Tasks stack.
 */
class Tasks
{
    /** @var    array<string,Task>   $stack   The tasks stack */
    private array $stack = [];

    /**
     * Contructor load cleaners.
     */
    public function __construct()
    {
        # --BEHAVIOR-- improveTaskAdd: Tasks
        dcCore::app()->callBehavior('improveTaskAdd', $this);

        uasort($this->stack, fn ($a, $b) => $a->properties->name <=> $b->properties->name);
        uasort($this->stack, fn ($a, $b) => $a->properties->priority <=> $b->properties->priority);
    }

    /**
     * Add an task.
     *
     * @param   Task    $task   The task instance
     *
     * @return  Tasks   Self instance
     */
    public function add(Task $task): Tasks
    {
        if (!isset($this->stack[$task->properties->id])) {
            $this->stack[$task->properties->id] = $task;
        }

        return $this;
    }

    /**
     * Get all tasks.
     *
     * @return  array<string,Task>  The tasks stack
     */
    public function dump(): array
    {
        return $this->stack;
    }

    /**
     * Get a task.
     *
     * @param   string  $id     The task id
     *
     * @return  null|Task   The task
     */
    public function get(string $id): ?Task
    {
        return $this->stack[$id] ?? null;
    }
}
