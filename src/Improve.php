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
use dcModuleDefine;
use Dotclear\Helper\File\{
    Files,
    Path
};
use Exception;

/**
 * Improve main class
 */
class Improve
{
    /** @var    Tasks   $tasks  The tasks stack instance */
    public readonly Tasks $tasks;

    /** @var    Logs    $logs   The logs stack instance */
    public readonly Logs $logs;

    /** @var    array   $readfile_extensions    Allowed file extensions to open */
    private static $readfile_extensions = [
        'php', 'xml', 'js', 'css', 'csv', 'html', 'htm', 'txt', 'md', 'po',
    ];

    /** @var    Improve     $instance   Improve instance */
    private static $instance;

    /**
     * Constructor
     */
    protected function __construct()
    {
        $this->logs  = new Logs();
        $this->tasks = new Tasks();

        // mark some tasks as disabled (by settings)
        $disable = explode(';', (string) dcCore::app()->blog?->settings->get(My::id())->get('disabled'));
        foreach ($disable as $id) {
            $this->tasks->get($id)?->disable();
        }
    }

    protected function __clone()
    {
    }

    public function __wakeup()
    {
        throw new Exception('nope');
    }

    /**
     * Get singleton instance.
     *
     * @return  Improve     Improve instance
     */
    public static function instance(): Improve
    {
        if (!is_a(self::$instance, Improve::class)) {
            self::$instance = new Improve();
        }

        return self::$instance;
    }

    public function fix(dcModuleDefine $module, array $tasks): float
    {
        $time_start = microtime(true);

        $workers = [];
        foreach ($tasks as $id) {
            if (!$this->tasks->get($id)?->isDisabled()
                && $this->tasks->get($id)?->isConfigured()
            ) {
                $workers[] = $this->tasks->get($id);
            }
        }
        foreach ($workers as $task) {
            // trace all path and action in logs
            $this->logs->add(My::id(), __('Begin'), [$task->properties->id]);
            // info: set current module
            $task->setModule($module);
            $task->setPath(__('Begin'), '', true);
            // action: open module
            $task->openModule();
        }
        if (!$module->get('root_writable') || !is_writable($module->get('root'))) {
            throw new Exception(__('Module path is not writable'));
        }
        $tree = self::getModuleFiles($module->get('root'));
        foreach ($tree as $file) {
            if (!file_exists($file[0])) {
                continue;
            }
            foreach ($workers as $task) {
                // trace all path and action in logs
                $this->logs->add(My::id(), $file[0], [$task->properties->id]);
                // info: set current path
                $task->setPath($file[0], $file[1], $file[2]);
            }
            if (!$file[2]) {
                foreach ($workers as $task) {
                    // action: open a directory. full path
                    $task->openDirectory();
                }
            } else {
                foreach ($workers as $task) {
                    // action: before openning a file. full path, extension
                    $task->openFile();
                }
                if (in_array($file[1], self::$readfile_extensions)) {
                    if (false !== ($content = file_get_contents($file[0]))) {
                        $no_content = empty($content);
                        foreach ($workers as $task) {
                            // action: read a file content. full path, extension, content
                            $task->readFile($content);
                            if (empty($content) && !$no_content) {
                                throw new Exception(sprintf(
                                    __('File content has been removed: %s by %s'),
                                    $file[0],
                                    $task->properties->name
                                ));
                            }
                        }
                        Files::putContent($file[0], $content);
                    }
                    foreach ($workers as $task) {
                        // action: after closing a file. full path, extension
                        $task->closeFile();
                    }
                }
            }
        }
        foreach ($workers as $task) {
            // trace all path and action in logs
            $this->logs->add(My::id(), __('End'), [$task->properties->id]);
            // info: set current module
            $task->setPath(__('End'), '', true);
            // action: close module
            $task->closeModule();
        }
        // info: get acions reports
        foreach ($workers as $task) {
            foreach (['success', 'warning', 'error'] as $type) {
                if (!$task->{$type}->empty()) {
                    $this->logs->add($task->properties->id, $type, $task->{$type}->dump());
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
}
