<?php

declare(strict_types=1);

namespace Dotclear\Plugin\improve;

use Dotclear\Helper\File\Path;

/**
 * @brief       improve module helper class.
 * @ingroup     improve
 *
 * Help to load module configuration file (_define.php)
 * and gather information about it.
 *
 * @author      Jean-Christian Denis
 * @copyright   GPL-2.0 https://www.gnu.org/licenses/gpl-2.0.html
 */
class Module
{
    /**
     * Current module properties.
     *
     * @var     array<string, mixed>    $properties
     */
    private $properties = [];

    /**
     * Constructor.
     *
     * @param   string                  $type           Module type, plugin or theme
     * @param   string                  $id             Module id
     * @param   array<string, mixed>    $properties     Module properties
     */
    public function __construct(string $type, string $id, array $properties = [])
    {
        $this->loadDefine($id, $properties['root']);
        $this->properties = array_merge($this->properties, self::sanitizeModule($type, $id, $properties));
    }

    /**
     * Get module properties.
     *
     * @return  array<string, mixed>    The properties
     */
    public function get(): array
    {
        return $this->properties;
    }

    /**
     * Get clean properties of registered module.
     *
     * @param   string                  $type           Module type, plugin or theme
     * @param   string                  $id             Module id
     * @param   array<string, mixed>    $properties     Module properties
     *
     * @return  array<string, mixed>    Module properties
     */
    public static function clean(string $type, string $id, array $properties): array
    {
        $module = new self($type, $id, $properties);

        return $module->get();
    }

    /**
     * Replicate dcModule::loadDefine.
     *
     * @param   string  $id     Module id
     * @param   string  $root   Module path
     *
     * @return  bool    True on success
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
     * Replicate dcModule::registerModule.
     *
     * @param   string          $name           The module name
     * @param   string          $desc           The module description
     * @param   string          $author         The module author
     * @param   string          $version        The module version
     * @param   string|array    $properties     The properties
     *
     * @return  bool    True on success
     * @phpstan-ignore-next-line
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
                'repository'        => '',
            ],
            $properties
        );

        return true;
    }

    /**
     * Replicate ModulesList::sanitizeModule.
     *
     * @param   string                  $type           Module type
     * @param   string                  $id             Module id
     * @param   array<string, mixed>    $properties     Module properties
     *
     * @return  array<string, mixed>    Sanitized module properties
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
                'dc_min'            => 0,
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
                'sroot' => Path::real($properties['root']),
            ]
        );
    }

    /**
     * Replicate ModulesList::sanitizeString.
     *
     * @param   string  $str    String to sanitize
     *
     * @return  string  Sanitized string
     */
    public static function sanitizeString(string $str): string
    {
        return (string) preg_replace('/[^A-Za-z0-9\@\#+_-]/', '', strtolower($str));
    }
}
