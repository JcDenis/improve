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

namespace Dotclear\Plugin\improve\Task;

use Dotclear\Helper\File\{
    Files,
    Path
};
use Dotclear\Plugin\improve\{
    Task,
    TaskDescriptor
};

/**
 * Improve action module Dotclear depreciated
 */
class DcDeprecated extends Task
{
    /** @var array Deprecated functions [filetype [pattern, deprecated, replacement, version, help link]] */
    private $deprecated = ['php' => [], 'js' => []];

    /** @var boolean Stop parsing files */
    private $stop_scan = false;

    protected function getProperties(): TaskDescriptor
    {
        return new TaskDescriptor(
            id: 'dcdeprecated',
            name: __('Dotclear deprecated'),
            description: __('Search for use of deprecated Dotclear functions'),
            configurator: false,
            types: ['plugin', 'theme'],
            priority: 520
        );
    }

    protected function init(): bool
    {
        $path = Path::real(__DIR__ . '/dcdeprecated');
        if (!$path || !is_dir($path) || !is_readable($path)) {
            return false;
        }
        $files = Files::scandir($path);

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

        return true;
    }

    public function isConfigured(): bool
    {
        return true;
    }

    public function openDirectory(): ?bool
    {
        $skipped         = $this->stop_scan;
        $this->stop_scan = false;
        if (preg_match('/\/(dcdeprecated)(\/.*?|)$/', $this->path_full)) {
            if (!$skipped) {
                $this->success->add(__('Skip directory'));
            }
            $this->stop_scan = true;
        }

        return null;
    }

    public function readFile(&$content): ?bool
    {
        if ($this->stop_scan || !in_array($this->path_extension, array_keys($this->deprecated))) {
            return null;
        }
        foreach ($this->deprecated[$this->path_extension] as $d) {
            if (preg_match('/' . $d[0] . '/', $content)) {
                $this->warning->add(sprintf(__('Possible use of deprecated "%s", you should use "%s" instead since Dotclear %s.'), $d[1], __($d[2]), $d[3]) . (empty($d[4]) ? '' : ' <a href="' . $d['4'] . '">' . __('Help') . '</a> '));
            }
        }

        return true;
    }
}
