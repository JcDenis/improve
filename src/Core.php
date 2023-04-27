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

use ArrayObject;
use dcCore;
use dcLog;
use dcModuleDefine;
use Dotclear\Helper\File\{
    Files,
    Path
};
use Exception;

/**
 * Improve main class
 */
class Core
{
    /** @var array  Allowed file extensions to open */
    private static $readfile_extensions = [
        'php', 'xml', 'js', 'css', 'csv', 'html', 'htm', 'txt', 'md', 'po',
    ];

    /** @var array<Action> $actions Loaded actions modules */
    private $actions = [];

    /** @var array<string>  $disabled Disabled actions modules */
    private $disabled = [];

    /** @var array<string, array> $logs Logs by actions modules */
    private $logs = [];

    /** @var array<string, boolean>  $has_log   Has log of given type */
    private $has_log = ['success' => false, 'warning' => false, 'error' => false];

    /**
     * Constructor
     */
    public function __construct()
    {
        $disabled = explode(';', (string) dcCore::app()->blog->settings->get(My::id())->get('disabled'));
        $list     = new ArrayObject();

        try {
            dcCore::app()->callBehavior('improveAddAction', $list);

            foreach ($list as $action) {
                if (($action instanceof Action) && !isset($this->actions[$action->id()])) {
                    if (in_array($action->id(), $disabled)) {
                        $this->disabled[$action->id()] = $action->name();
                    } else {
                        $this->actions[$action->id()] = $action;
                    }
                }
            }
        } catch (Exception $e) {
            dcCore::app()->error->add($e->getMessage());
        }
        uasort($this->actions, [$this, 'sortModules']);
    }

    public static function id(): string
    {
        return basename(dirname(__DIR__));
    }

    public static function name(): string
    {
        return __((string) dcCore::app()->plugins->moduleInfo(My::id(), 'name'));
    }

    public function getLogs(): array
    {
        return $this->logs;
    }

    public function hasLog(string $type): bool
    {
        return array_key_exists($type, $this->has_log) && $this->has_log[$type];
    }

    public function writeLogs(): int
    {
        if (empty($this->logs)) {
            return 0;
        }
        $cur = dcCore::app()->con->openCursor(dcCore::app()->prefix . dcLog::LOG_TABLE_NAME);
        $cur->setField('log_msg', json_encode($this->logs));
        $cur->setField('log_table', My::id());

        return dcCore::app()->log->addLog($cur);
    }

    public function readLogs(int $id): array
    {
        $rs = dcCore::app()->log->getLogs(['log_table' => My::id(), 'log_id' => $id, 'limit' => 1]);
        if ($rs->isEmpty()) {
            return [];
        }
        dcCore::app()->log->delLogs($rs->f('log_id'));

        $res = json_decode($rs->f('log_msg'), true);

        return is_array($res) ? $res : [];
    }

    public function parseLogs(int $id): array
    {
        $logs = $this->readLogs($id);
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
     * Get a loaded action module
     *
     * @param  string $id Module id
     *
     * @return Action     action instance
     */
    public function module(string $id): ?Action
    {
        if (empty($id)) {
            return null;
        }

        return $this->actions[$id] ?? null;
    }

    /**
     * Get all loaded action modules
     *
     * @return Action[]     action instance
     */
    public function modules(): array
    {
        return $this->actions;
    }

    /**
     * Get disabled action modules
     *
     * @return array    Array of id/name modules
     */
    public function disabled(): array
    {
        return $this->disabled;
    }

    public function fixModule(dcModuleDefine $module, array $actions): float
    {
        $time_start = microtime(true);

        $workers = [];
        foreach ($actions as $action) {
            if (isset($this->actions[$action]) && $this->actions[$action]->isConfigured()) {
                $workers[] = $this->actions[$action];
            }
        }
        foreach ($workers as $action) {
            // trace all path and action in logs
            $this->logs[My::id()][__('Begin')][] = $action->id();
            // info: set current module
            $action->setModule($module);
            $action->setPath(__('Begin'), '', true);
            // action: open module
            $action->openModule();
        }
        if (!$module->get('root_writable') || !is_writable($module->get('root'))) {
            throw new Exception(__('Module path is not writable'));
        }
        $tree = self::getModuleFiles($module->get('root'));
        foreach ($tree as $file) {
            if (!file_exists($file[0])) {
                continue;
            }
            foreach ($workers as $action) {
                // trace all path and action in logs
                $this->logs[My::id()][$file[0]][] = $action->id();
                // info: set current path
                $action->setPath($file[0], $file[1], $file[2]);
            }
            if (!$file[2]) {
                foreach ($workers as $action) {
                    // action: open a directory. full path
                    $action->openDirectory();
                }
            } else {
                foreach ($workers as $action) {
                    // action: before openning a file. full path, extension
                    $action->openFile();
                }
                if (in_array($file[1], self::$readfile_extensions)) {
                    if (false !== ($content = file_get_contents($file[0]))) {
                        $no_content = empty($content);
                        foreach ($workers as $action) {
                            // action: read a file content. full path, extension, content
                            $action->readFile($content);
                            if (empty($content) && !$no_content) {
                                throw new Exception(sprintf(
                                    __('File content has been removed: %s by %s'),
                                    $file[0],
                                    $action->name()
                                ));
                            }
                        }
                        Files::putContent($file[0], $content);
                    }
                    foreach ($workers as $action) {
                        // action: after closing a file. full path, extension
                        $action->closeFile();
                    }
                }
            }
        }
        foreach ($workers as $action) {
            // trace all path and action in logs
            $this->logs[My::id()][__('End')][] = $action->id();
            // info: set current module
            $action->setPath(__('End'), '', true);
            // action: close module
            $action->closeModule();
        }
        // info: get acions reports
        foreach ($workers as $action) {
            $this->logs[$action->id()] = $action->getLogs();
            foreach ($this->has_log as $type => $v) {
                if ($action->hasLog($type)) {
                    $this->has_log[$type] = true;
                }
            }
        }

        return round(microtime(true) - $time_start, 5);
    }

    private static function getModuleFiles(string $path, string $dir = '', array $res = []): array
    {
        $path = Path::real($path);
        if (!$path) {
            return [];
        }
        if (!is_dir($path) || !is_readable($path)) {
            return [];
        }
        if (!$dir) {
            $dir = $path;
        }
        $res[] = [$dir, '', false];
        $files = Files::scandir($path);

        foreach ($files as $file) {
            if (substr($file, 0, 1) == '.') {
                continue;
            }
            if (is_dir($path . '/' . $file)) {
                $res = self::getModuleFiles(
                    $path . '/' . $file,
                    $dir . '/' . $file,
                    $res
                );
            } else {
                $res[] = [$dir . '/' . $file, Files::getExtension($file), true];
            }
        }

        return $res;
    }

    public function getURL(array $params = []): string
    {
        return dcCore::app()->adminurl->get('admin.plugin.' . My::id(), $params, '&');
    }

    /**
     * Check and clean file extension
     *
     * @param  string|array  $in    Extension(s) to clean
     * @return array                Cleaned extension(s)
     */
    public static function cleanExtensions($in): array
    {
        $out = [];
        if (!is_array($in)) {
            $in = explode(',', $in);
        }
        if (!empty($in)) {
            foreach ($in as $v) {
                $v = trim(Files::getExtension('a.' . $v));
                if (!empty($v)) {
                    $out[] = $v;
                }
            }
        }

        return $out;
    }

    /**
     * Sort modules by priority then name
     *
     * @param  Action    $a  ImproveAction instance
     * @param  Action    $b  ImproveAction instance
     *
     * @return integer              Is higher
     */
    private function sortModules(Action $a, Action $b): int
    {
        if ($a->priority() == $b->priority()) {
            return strcasecmp($a->name(), $b->name());
        }

        return $a->priority() < $b->priority() ? -1 : 1;
    }
}
