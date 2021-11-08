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
/**
 * This class manage all actions sub-class
 */
class Improve
{
    /** @var array  Allowed file extensions to open */
    public static $readfile_extensions = [
        'php', 'xml', 'js', 'css', 'csv', 'html', 'htm', 'txt', 'md'
    ];

    /** @var dcCore     dcCore instance */
    private $core;

    /** @var ImproveAction[]   Loaded actions modules */
    private $actions = [];

    /** @var array<string>  Disabled actions modules */
    private $disabled = [];

    /** @var array<string, array>   Logs by actions modules */
    private $logs = [];

    /** @var array<string, boolean>     Has log of given type */
    private $has_log = ['success' => false, 'warning' => false, 'error' => false];

    /**
     * Constructor
     *
     * @param dcCore $core dcCore instance
     */
    public function __construct(dcCore $core)
    {
        $core->blog->settings->addNamespace('improve');
        $this->core = &$core;
        $disabled   = explode(';', (string) $core->blog->settings->improve->disabled);
        $list       = new arrayObject();

        try {
            $this->core->callBehavior('improveAddAction', $list, $this->core);

            foreach ($list as $action) {
                if (is_a($action, 'ImproveAction') && !isset($this->actions[$action->id()])) {
                    if (in_array($action->id(), $disabled)) {
                        $this->disabled[$action->id()] = $action->name();
                    } else {
                        $this->actions[$action->id()] = $action;
                    }
                }
            }
        } catch (Exception $e) {
            $core->error->add($e->getMessage());
        }
        uasort($this->actions, [$this, 'sortModules']);
    }

    public function getLogs(): array
    {
        return $this->logs;
    }

    public function hasLog(string $type): bool
    {
        return array_key_exists($type, $this->has_log) && $this->has_log[$type];
    }

    public function writeLogs(): string
    {
        if (empty($this->logs)) {
            return '';
        }
        $cur            = $this->core->con->openCursor($this->core->prefix . 'log');
        $cur->log_msg   = serialize($this->logs);
        $cur->log_table = 'improve';
        $id             = $this->core->log->addLog($cur);

        return $id;
    }

    public function readLogs(int $id): array
    {
        $rs = $this->core->log->getLogs(['log_table' => 'improve', 'log_id' => $id, 'limit' => 1]);
        if ($rs->isEmpty()) {
            return [];
        }
        $this->core->log->delLogs($rs->log_id);

        $res = unserialize($rs->log_msg);

        return is_array($res) ? $res : [];
    }

    public function parselogs(int $id): array
    {
        $logs = $this->readLogs($id);
        if (empty($logs)) {
            return [];
        }
        $lines = [];
        foreach ($logs['improve'] as $path => $tools) {
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
     * @return ImproveAction     ImproveAction instance
     */
    public function module(string $id): ?ImproveAction
    {
        if (empty($id)) {
            return null;
        }

        return $this->actions[$id] ?? null;
    }

    /**
     * Get all loaded action modules
     *
     * @return ImproveAction[]     ImproveAction instance
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

    public function fixModule(string $type, string $id, array $properties, array $actions): float
    {
        $time_start = microtime(true);
        $module     = ImproveDefinition::clean($type, $id, $properties);

        $workers = [];
        foreach ($actions as $action) {
            if (isset($this->actions[$action]) && $this->actions[$action]->isConfigured()) {
                $workers[] = $this->actions[$action];
            }
        }
        foreach ($workers as $action) {
            // trace all path and action in logs
            $this->logs['improve'][__('Begin')][] = $action->id();
            // info: set current module
            $action->setModule($module);
            $action->setPath(__('Begin'), '', true);
            // action: open module
            $action->openModule();
        }
        if (!isset($module['sroot']) || !$module['root_writable'] || !is_writable($module['sroot'])) {
            throw new Exception(__('Module path is not writable'));
        }
        $tree = self::getModuleFiles($module['sroot']);
        foreach ($tree as $file) {
            if (!file_exists($file[0])) {
                continue;
            }
            foreach ($workers as $action) {
                // trace all path and action in logs
                $this->logs['improve'][$file[0]][] = $action->id();
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
                        files::putContent($file[0], $content);
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
            $this->logs['improve'][__('End')][] = $action->id();
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
        $path = path::real($path);
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
        $files = files::scandir($path);

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
                $res[] = [$dir . '/' . $file, files::getExtension($file), true];
            }
        }

        return $res;
    }

    public function getURL(array $params = []): string
    {
        return $this->core->adminurl->get('admin.plugin.improve', $params, '&');
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
                $v = trim(files::getExtension('a.' . $v));
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
     * @param  ImproveAction    $a  ImproveAction instance
     * @param  ImproveAction    $b  ImproveAction instance
     *
     * @return integer              Is higher
     */
    private function sortModules(ImproveAction $a, ImproveAction $b): int
    {
        if ($a->priority() == $b->priority()) {
            return strcasecmp($a->name(), $b->name());
        }

        return $a->priority() < $b->priority() ? -1 : 1;
    }
}

class ImproveDefinition
{
    /** @var array  Current module properties */
    private $properties = [];

    /**
     * Constructor
     *
     * @param string    $type           Module type, plugin or theme
     * @param string    $id             Module id
     * @param array     $properties     Module properties
     */
    public function __construct(string $type, string $id, array $properties = [])
    {
        $this->loadDefine($id, $properties['root']);
        $this->properties = array_merge($this->properties, self::sanitizeModule($type, $id, $properties));
    }

    /**
     * Get module properties
     *
     * @return array    The properties
     */
    public function get(): array
    {
        return $this->properties;
    }

    /**
     * Get clean properties of registered module
     *
     * @param string    $type           Module type, plugin or theme
     * @param string    $id             Module id
     * @param array     $properties     Module properties
     *
     * @return array                    Module properties
     */
    public static function clean(string $type, string $id, array $properties): array
    {
        $p = new self($type, $id, $properties);

        return $p->get();
    }

    /**
     * Replicate dcModule::loadDefine
     *
     * @param  string $id   Module id
     * @param  string $root Module path
     *
     * @return boolean      Success
     */
    private function loadDefine(string $id, string $root): bool
    {
        if (file_exists($root . '/_define.php')) {
            ob_start();
            require $root . '/_define.php';
            ob_end_clean();
        }

        return true;
    }

    /**
     * Replicate dcModule::registerModule
     *
     * @param   string          $name           The module name
     * @param   string          $desc           The module description
     * @param   string          $author         The module author
     * @param   string          $version        The module version
     * @param   string|array    $properties     The properties
     *
     * @return  boolean                 Success
     */
    private function registerModule(string $name, string $desc, string $author, string $version, $properties = []): bool
    {
        if (!is_array($properties)) {
            $args       = func_get_args();
            $properties = [];
            if (isset($args[4])) {
                $properties['permissions'] = $args[4];
            }
            if (isset($args[5])) {
                $properties['priority'] = (int) $args[5];
            }
        }

        $this->properties = array_merge(
            [
                'permissions'       => null,
                'priority'          => 1000,
                'standalone_config' => false,
                'type'              => null,
                'enabled'           => true,
                'requires'          => [],
                'settings'          => [],
                'repository'        => ''
            ],
            $properties
        );

        return true;
    }

    /**
     * Replicate adminModulesList::sanitizeModule
     *
     * @param  string $type       Module type
     * @param  string $id         Module id
     * @param  array  $properties Module properties
     *
     * @return array              Sanitized module properties
     */
    public static function sanitizeModule(string $type, string $id, array $properties): array
    {
        $label = empty($properties['label']) ? $id : $properties['label'];
        $name  = __(empty($properties['name']) ? $label : $properties['name']);
        $oname = empty($properties['name']) ? $label : $properties['name'];

        return array_merge(
            # Default values
            [
                'desc'              => '',
                'author'            => '',
                'version'           => 0,
                'current_version'   => 0,
                'root'              => '',
                'root_writable'     => false,
                'permissions'       => null,
                'parent'            => null,
                'priority'          => 1000,
                'standalone_config' => false,
                'support'           => '',
                'section'           => '',
                'tags'              => '',
                'details'           => '',
                'sshot'             => '',
                'score'             => 0,
                'type'              => null,
                'requires'          => [],
                'settings'          => [],
                'repository'        => '',
                'dc_min'            => 0
            ],
            # Module's values
            $properties,
            # Clean up values
            [
                'id'    => $id,
                'sid'   => self::sanitizeString($id),
                'type'  => $type,
                'label' => $label,
                'name'  => $name,
                'oname' => $oname,
                'sname' => self::sanitizeString($name),
                'sroot' => path::real($properties['root'])
            ]
        );
    }

    /**
     * Replicate adminModulesList::sanitizeString
     *
     * @param  string   $str    String to sanitize
     *
     * @return string           Sanitized string
     */
    public static function sanitizeString(string $str): string
    {
        return (string) preg_replace('/[^A-Za-z0-9\@\#+_-]/', '', strtolower($str));
    }
}
