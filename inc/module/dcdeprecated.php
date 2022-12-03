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

namespace plugins\improve\module;

/* improve */
use plugins\improve\action;

/* clearbricks */
use files;
use path;

/**
 * Improve action module Dotclear depreciated
 */
class dcdeprecated extends action
{
    /** @var array Deprecated functions [filetype [pattern, deprecated, replacement, version, help link]] */
    private $deprecated = ['php' => [], 'js' => []];

    protected function init(): bool
    {
        $this->setProperties([
            'id'          => 'dcdeprecated',
            'name'        => __('Dotclear deprecated'),
            'description' => __('Search for use of deprecated Dotclear functions'),
            'priority'    => 520,
            'types'       => ['plugin', 'theme'],
        ]);
        $this->loadDeprecatedDefinition();

        return true;
    }

    private function loadDeprecatedDefinition()
    {
        $path = path::real(__DIR__ . '/dcdeprecated');
        if (!$path) {
            return [];
        }
        if (!is_dir($path) || !is_readable($path)) {
            return [];
        }
        $files = files::scandir($path);

        foreach ($files as $file) {
            if (substr($file, 0, 1) == '.') {
                continue;
            }
            $tmp = require $path . '/' . $file;
            if (is_array($tmp) && isset($tmp['php'])) {
                $this->deprecated['php'] = array_merge($this->deprecated['php'], $tmp['php']);
            }
            if (is_array($tmp) && isset($tmp['js'])) {
                $this->deprecated['js'] = array_merge($this->deprecated['js'], $tmp['js']);
            }
        }
    }

    public function isConfigured(): bool
    {
        return true;
    }

    public function readFile(&$content): ?bool
    {
        if (!in_array($this->path_extension, array_keys($this->deprecated))) {
            return null;
        }
        foreach ($this->deprecated[$this->path_extension] as $d) {
            if (preg_match('/' . $d[0] . '/i', $content)) {
                $this->setWarning(sprintf(__('Possible use of deprecated "%s", you should use "%s" instead since Dotclear %s.'), $d[1], __($d[2]), $d[3]) . (empty($d[4]) ? '' : ' <a href="' . $d['4'] . '">' . __('Help') . '</a> '));
            }
        }

        return true;
    }
}
