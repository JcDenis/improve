<?php
/**
 * @brief improve, a plugin for Dotclear 2
 * 
 * @package Dotclear
 * @subpackage plugin
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
    public static $readfile_extensions = [
        'php', 'xml', 'js', 'css', 'csv', 'html', 'htm', 'txt', 'md'
    ];
    private $core;
    private $actions = [];

    public function __construct(dcCore $core)
    {
        $this->core = &$core;
        $core->blog->settings->addNamespace('improve');
        $list = new arrayObject();

        try {
            $this->core->callBehavior('improveAddAction', $list, $this->core);

            foreach($list as $action) {
                if ($action instanceOf ImproveAction && !isset($this->actions[$action->id])) {
                    $this->actions[$action->id] = $action;
                }
            }
        } catch (Exception $e) {
            $core->error->add($e->getMessage());
        }
        uasort($this->actions, [$this, 'sortModules']);
    }

    public function module(string $id): ?ImproveAction
    {
        if (empty($id)) {
            return $this->actions;
        }
        return $this->actions[$id] ?? null;
    }

    public function modules(): ?array
    {
        if (empty($id)) {
            return $this->actions;
        }
        return $this->actions[$id] ?? null;
    }

    public function fix(string $type, string $id, array $module, array $actions): int
    {
        $module = ImproveDefinition::clean($id, $module);

        $workers = [];
        foreach($actions as $action) {
            if (isset($this->actions[$action]) && $this->actions[$action]->isConfigured()) {
                $workers[] = $this->actions[$action];
            }
        }
        foreach($workers as $action) {
            // action: 
            // open module
            $action->openModule($type, $module);
        }
        if (!isset($module['sroot']) || !$module['root_writable'] || !is_writable($module['sroot'])) {
            throw new Exception(__('Module path is not writable'));
        }
        $tree = self::getModuleFiles($module['sroot']);
        foreach($tree as $file) {
            if (!file_exists($file[0])) {
                continue;
            }
            if (!$file[2]) {
                foreach($workers as $action) {
                    // action: 
                    // open a directory. full path
                    $action->openDirectory($file[0]);
                }
            } else {
                foreach($workers as $action) {
                    // action: 
                    // before openning a file. full path, extension
                    $action->openFile($file[0], $file[1]);
                }
                if (in_array($file[1], self::$readfile_extensions)) {
                    if (false !== ($content = file_get_contents($file[0]))) {
                        $no_content = empty($content);
                        foreach($workers as $action) {
                            // action: 
                            // read a file content. full path, extension, content
                            $action->readFile($file[0], $file[1], $content);
                            if (empty($content) && !$no_content) {
                                throw new Exception(sprintf(
                                    __('File content has been removed: %s by %s'), $file[0], $action->name
                                ));
                            }
                        }
                        files::putContent($file[0], $content);
                    }
                    foreach($workers as $action) {
                        // action: 
                        // after closing a file. full path, extension
                        $action->closeFile($file[0], $file[1]);
                    }
                }
            }
        }
        // action: 
        // close module
        foreach($workers as $action) {
            $action->closeModule($type, $module);
        }
        foreach($workers as $action) {
            if ($action->hasNotice()) {
                dcPage::addErrorNotice($action->name . ' : ' . implode(', ', $action->getNotice()));
            }
        }
        foreach($workers as $action) {
            if ($action->hasNotice(false)) {
                dcPage::addWarningNotice($action->name . ' : ' . implode(', ', $action->getNotice(false)));
            }
        }
        return count($tree);
    }

    private static function getModuleFiles(string $path, string $dir = '', array $res = []): array
    {
        $path = path::real($path);
        if (!is_dir($path) || !is_readable($path)) {
            return [];
        }
        if (!$dir) {
            $dir = $path;
        }
        $res[] = [$dir, '', false];
        $files = files::scandir($path);

        foreach($files AS $file) {
            if (substr($file, 0, 1) == '.') {
                continue;
            }
            if (is_dir($path . '/' . $file)) {
                $res = self::getModuleFiles(
                    $path . '/' . $file, $dir . '/' . $file,
                    $res
                );
            } else {
                $res[] = [empty($dir) ? $file : $dir . '/' . $file, files::getExtension($file), true];
            }
        }
        return $res;
    }

    public function getURL(array $params = []): string
    {
        return $this->core->adminurl->get('admin.plugin.improve', $params);
    }

    public static function cleanExtensions(mixed $in): array
    {
        $out = [];
        if (!is_array($in)) {
            $in = explode(',', $in);
        }
        if (!empty($in)) {
            foreach($in as $v) {
                $v = trim(files::getExtension('a.' . $v));
                if (!empty($v)) {
                    $out[] = $v;
                }
            }
        }
        return $out;
    }

    private function sortModules(improveAction $a, improveAction $b): int
    {
        if ($a->priority == $b->priority) {
            return strcasecmp($a->name, $b->name);
        }
        return $a->priority < $b->priority ? -1 : 1;
    }
}

class ImproveDefinition
{
    private $properties = [];

    public function __construct(string $id, array $properties = [])
    {
        $this->loadDefine($id, $properties['root']);

        $this->properties = array_merge($this->properties, self::sanitizeModule($id, $properties));
    }

    public function get()
    {
        return $this->properties;
    }

    public static function clean($id, $properties)
    {
        $p = new self($id, $properties);
        return $p->get();
    }

    private function loadDefine($id, $root)
    {
        if (file_exists($root . '/_define.php')) {
            $this->id    = $id;
            $this->mroot = $root;
            ob_start();
            require $root . '/_define.php';
            ob_end_clean();
        }
    }

    # adapt from class.dc.modules.php
    private function registerModule($name, $desc, $author, $version, $properties = [])
    {
        if (!is_array($properties)) {
            $args       = func_get_args();
            $properties = [];
            if (isset($args[4])) {
                $properties['permissions'] = $args[4];
            }
            if (isset($args[5])) {
                $properties['priority'] = (integer) $args[5];
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
            ], $properties
        );
    }

    # adapt from lib.moduleslist.php
    public static function sanitizeModule(string $id, array $properties): array
    {
        $label = empty($properties['label']) ? $id : $properties['label'];
        $name  = __(empty($properties['name']) ? $label : $properties['name']);
        $oname  = empty($properties['name']) ? $label : $properties['name'];

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
                'require'           => [],
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
                'label' => $label,
                'name'  => $name,
                'oname' => $oname,
                'sname' => self::sanitizeString($name),
                'sroot' => path::real($properties['root'])
            ]
        );
    }

    # taken from lib.moduleslist.php
    public static function sanitizeString(string $str): string
    {
        return preg_replace('/[^A-Za-z0-9\@\#+_-]/', '', strtolower($str));
    }
}