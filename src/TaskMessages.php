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

/**
 * Task messages group.
 */
class TaskMessages
{
    /** @ var   array<string,array>     $stack  The messages by path stack */
    private array $stack;

    /** @ var   string  $path  The current path */
    private string $path = 'root';

    /**
     * Set current working path.
     *
     * @param   string  $path    The path
     */
    public function path(string $path = 'root'): void
    {
        $this->path = $path;
    }

    /**
     * Check if there are messages.
     *
     * return   bool    True if not empty
     */
    public function empty(): bool
    {
        return empty($this->stack);
    }

    /**
     * Add a message for current path.
     *
     * @param   string  $message    The message
     */
    public function add(string $message): void
    {
        $this->stack[$this->path][] = $message;
    }

    /**
     * Get a path messages.
     *
     * @param   string  The path
     *
     * @return  array   The messages
     */
    public function get(string $path): array
    {
        return $this->stack[$path] ?? [];
    }

    /**
     * Get all messages
     *
     * @return  array<string,array>     The messages stack
     */
    public function dump(): array
    {
        return $this->stack;
    }
}
