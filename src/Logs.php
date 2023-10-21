<?php

declare(strict_types=1);

namespace Dotclear\Plugin\improve;

use Dotclear\App;

/**
 * @brief       improve logs helper class.
 * @ingroup     improve
 *
 * @author      Jean-Christian Denis
 * @copyright   GPL-2.0 https://www.gnu.org/licenses/gpl-2.0.html
 */
class Logs
{
    /**
     * The logs stack.
     *
     * @var     array<string, array<string, array<int, string>|array<string, array<int, string>>>>  $stack
     */
    private array $stack;

    /**
     * Has log of given type.
     *
     * @var     array<string, bool>     $has
     */
    private $has = [
        'success' => false,
        'warning' => false,
        'error'   => false,
    ];

    /**
     * Add a log.
     *
     * @param   string              $task   The task ID
     * @param   string              $path   The path
     * @param   array<int, string>  $msgs   The messages
     */
    public function add(string $task, string $path, array $msgs): void
    {
        // Get existing messages
        $logs = $this->stack[$task][$path] ?? [];

        // Merge with new messages
        $this->stack[$task][$path] = array_merge($logs, $msgs);

        // Check message type
        if (in_array($path, ['success', 'warning', 'error'])) {
            $this->has[$path] = true;
        }
    }

    /**
     * Check if log of type $type exists.
     *
     * @param   string  $type   The log type (success, warning, error)
     *
     * @return  bool    True on exist
     */
    public function has(string $type): bool
    {
        return (bool) ($this->has[$type] ?? false);
    }

    /**
     * Write logs to dcLogs.
     *
     * @return  int     The new log ID
     */
    public function write(): int
    {
        if (empty($this->stack)) {
            return 0;
        }
        $cur = App::log()->openLogCursor();
        $cur->setField('log_msg', json_encode($this->stack));
        $cur->setField('log_table', My::id());

        return App::log()->addLog($cur);
    }

    /**
     * Parse logs from dcLog.
     *
     * Returns logs by path -> type -> task -> message
     *
     * @param   int     $id The log ID
     *
     * @return  array<string, array<string, array<string, array<int|string, string|array<int, string>>>>>   The parse logs
     */
    public function parse(int $id): array
    {
        $logs = $this->read($id);
        if (empty($logs)) {
            return [];
        }
        $lines = [];
        foreach ($logs[My::id()] as $path => $tools) {
            $l_types = [];
            foreach (['success', 'warning', 'error'] as $type) {
                $l_tools = [];
                foreach ($tools as $tool) {
                    if (!is_string($tool)) {
                        continue;
                    }
                    $l_msg = [];
                    if (!empty($logs[$tool][$type][$path]) && is_array($logs[$tool][$type][$path])) {
                        foreach ($logs[$tool][$type][$path] as $msg) {
                            $l_msg[] = $msg;
                        }
                    }
                    if (!empty($l_msg)) {
                        $l_tools[$tool] = $l_msg;
                    }
                }
                if (!empty($l_tools)) {
                    $l_types[$type] = $l_tools;
                }
            }
            if (!empty($l_types)) {
                $lines[$path] = $l_types;
            }
        }

        return $lines;
    }

    /**
     * Get all messages
     *
     * @return  array<string, array<string, array<int, string>|array<string, array<int, string>>>>  The messages stack
     */
    public function dump(): array
    {
        return $this->stack;
    }

    /**
     * Read logs from dcLog.
     *
     * Logs are read once then deleted from dcLog.
     *
     * @param   int     $id     The log ID
     *
     * @return  array<string, array<string, array<int, string>|array<string, array<int, string>>>>  The logs
     */
    private function read(int $id): array
    {
        $rs = App::log()->getLogs(['log_table' => My::id(), 'log_id' => $id, 'limit' => 1]);
        if ($rs->isEmpty()) {
            return [];
        }
        App::log()->delLogs($rs->f('log_id'));

        $res = json_decode($rs->f('log_msg'), true);

        return is_array($res) ? $res : [];
    }
}
