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
use dcLog;

/**
 * Logs management.
 */
class Logs
{
    /** @ var   array<string,array>     $stack  The logs stack */
    private array $stack;

    /** @var    array<string,bool>  $has    Has log of given type */
    private $has = [
        'success' => false,
        'warning' => false,
        'error'   => false,
    ];

    /**
     * Add a log.
     *
     * @param   string  $task   The task ID
     * @param   string  $path   The path
     * @param   array   $msgs   The messages
     */
    public function add(string $task, string $path, array $msgs): void
    {
        // get existing messages
        $logs = $this->stack[$task][$path] ?? [];

        // merge with new messages
        $this->stack[$task][$path] = array_merge($logs, $msgs);

        // check message type
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
        $cur = dcCore::app()->con->openCursor(dcCore::app()->prefix . dcLog::LOG_TABLE_NAME);
        $cur->setField('log_msg', json_encode($this->stack));
        $cur->setField('log_table', My::id());

        return dcCore::app()->log->addLog($cur);
    }

    /**
     * Parse logs from dcLog.
     *
     * Returns logs by path -> type -> task -> message
     *
     * @param   int     $id The log ID
     *
     * @return  array   The parse logs
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
                    $l_msg = [];
                    if (!empty($logs[$tool][$type][$path])) {
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
     * @return  array<string,array>     The messages stack
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
     * @return  array   The logs
     */
    private function read(int $id): array
    {
        $rs = dcCore::app()->log->getLogs(['log_table' => My::id(), 'log_id' => $id, 'limit' => 1]);
        if ($rs->isEmpty()) {
            return [];
        }
        dcCore::app()->log->delLogs($rs->f('log_id'));

        $res = json_decode($rs->f('log_msg'), true);

        return is_array($res) ? $res : [];
    }
}
