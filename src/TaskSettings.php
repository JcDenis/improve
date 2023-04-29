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
use Exception;

/**
 * Task settings management.
 */
class TaskSettings
{
    /** @var    string  The setting prefix  */
    public const PREFIX = 'settings_';

    /** @ var   array<string,mixed>     $stack  The settings stack */
    private array $stack = [];

    /**
     * Constructor sets settings suffix.
     *
     * @param   string  $suffix     The settings suffix (ie taks id)
     */
    public function __construct(
        private string $suffix
    ) {
        if (is_null(dcCore::app()->blog)) {
            throw new Exception(__('Blog is not set'));
        }

        if (null !== ($settings = dcCore::app()->blog->settings->get(My::id())->get(self::PREFIX . $this->suffix))) {
            $settings    = json_decode($settings, true);
            $this->stack = is_array($settings) ? $settings : [];
        }
    }

    /**
     * Get a task setting.
     *
     * @param   string  $key    The setting ID
     *
     * @return  mixed   The setting value
     */
    public function get(string $key)
    {
        return $this->stack[$key] ?? null;
    }

    /**
     * Set one or more setting(s).
     *
     * @param   mixed   $settings     one or more settings
     * @param   mixed   $value        value for a single setting
     */
    public function set($settings, $value = null): void
    {
        foreach (is_array($settings) ? $settings : [$settings => $value] as $k => $v) {
            $this->stack[$k] = $v;
        }
    }

    /**
     * Save settings.
     */
    public function save(): void
    {
        if (!is_null(dcCore::app()->blog)) {
            dcCore::app()->blog->settings->get(My::id())->put(
                self::PREFIX . $this->suffix,
                json_encode($this->stack),
                'string',
                null,
                true,
                true
            );
            dcCore::app()->blog->triggerBlog();
        }
    }
}
