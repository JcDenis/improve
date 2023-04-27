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
use Exception;

/**
 * The Tasks stack.
 */
class Tasks
{
    /** @var    array<string,AbstractTask>   $stack   The tasks stack */
    private array $stack = [];

    /**
     * Contructor load cleaners.
     */
    public function __construct()
    {
        # --BEHAVIOR-- improveTaskAdd: Tasks
        dcCore::app()->callBehavior('improveTaskAdd', $this);

        uasort($this->stack, fn ($a, $b) => $a->name() <=> $b->name());
        uasort($this->stack, fn ($a, $b) => $a->priority() <=> $b->priority());
    }

    /**
     * Add an task.
     *
     * @param   AbstractTask    $task   The task instance
     *
     * @return  Tasks   Self instance
     */
    public function add(AbstractTask $task): Tasks
    {
        if (!isset($this->stack[$task->id()])) {
            $this->stack[$task->id()] = $task;
        }

        return $this;
    }

    /**
     * Get all tasks.
     *
     * @return  array<string,AbstractTask>  The tasks stack
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
     * @return  null|AbstractTask   The task
     */
    public function get(string $id): ?AbstractTask
    {
        return $this->stack[$id] ?? null;
    }
}
