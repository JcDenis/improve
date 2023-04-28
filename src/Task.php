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

use dcModuleDefine;
use Dotclear\Helper\Network\Http;

/**
 * Improve action class helper
 */
abstract class Task
{
    /** @var    TaskDescriptor  Task descriptor instance */
    public readonly TaskDescriptor $properties;

    /** @var    TaskMessages    Task success messages instance */
    public readonly TaskMessages $success;

    /** @var    TaskMessages    Task warning messages instance */
    public readonly TaskMessages $warning;

    /** @var    TaskMessages    Task error messages instance */
    public readonly TaskMessages $error;

    /** @var    TaskSettings    Task settings instance */
    protected readonly TaskSettings $settings;

    /** @var    dcModuleDefine  Current module */
    protected dcModuleDefine $module;

    /** @var    bool    Is disabled action */
    private bool $disabled = false;

    /** @var    string  Current full path */
    protected string $path_full = '';

    /** @var    string  Current file extension */
    protected string $path_extension = '';

    /** @var    null|bool    Current path is directory */
    protected ?bool $path_is_dir = null;

    /**
     * Action constructor inits properties and settings of a child class.
     */
    final public function __construct()
    {
        $this->success    = new TaskMessages();
        $this->warning    = new TaskMessages();
        $this->error      = new TaskMessages();
        $this->properties = $this->getProperties();
        $this->settings   = new TaskSettings($this->properties->id);
        $this->module     = new dcModuleDefine('undefined');

        $this->init();
    }

    /**
     * Get task description.
     *
     * @return     TaskDescriptor   The task description
     */
    abstract protected function getProperties(): TaskDescriptor;

    /**
     * Action initialisation function.
     *
     * It's called when an instance of ImproveAction child class is created.
     * Usefull to setup action class.
     *
     * @return     bool  True if initialisation is ok.
     */
    abstract protected function init(): bool;

    /**
     * Get a setting.
     *
     * @param   string  $key    The setting ID
     *
     * @return  mixed   Value of property or setting of action.
     */
    final public function get(string $key)
    {
        return $this->settings->get($key);
    }

    /**
     * Set task as disabled.
     */
    final public function disable()
    {
        $this->disabled = true;
    }

    /**
     * Check if task is disabled.
     *
     * @return  bool True on disabled
     */
    final public function isDisabled()
    {
        return $this->disabled;
    }

    /**
     * Do HTTP redirection.
     *
     * Used after settings form validation to save settings.
     *
     * @param   string  $url    The URL redirection
     */
    final protected function redirect(string $url): void
    {
        $this->settings->save();
        Http::redirect($url);
    }

    /**
     * Check if action class is well configured
     *
     * @return  boolean     True if class action is well configured
     */
    abstract public function isConfigured(): bool;

    /**
     * Get action configuration page header
     *
     * @return string Headers
     */
    public function header(): ?string
    {
        return null;
    }

    /**
     * Get configuraton gui
     *
     * If action class uses internal configuration,
     * it must share here html form content of its settings.
     * It must not use enclose bloc "form" nor button "save".
     * This function is also called to redirect form
     * after validation with $this->redirect($url);
     *
     * @param      string   $url    post form redirect url
     *
     * @return     string|null      A setting of action.
     */
    public function configure(string $url): ?string
    {
        return null;
    }

    /**
     * Set in class var current module definitions.
     *
     * @see Improve::sanitizeModule()
     *
     * @param      dcModuleDefine $module      Module definitons
     */
    final public function setModule(dcModuleDefine $module): bool
    {
        $this->module = $module;

        return true;
    }

    /**
     * Set in class var current path definitons.
     *
     * @param      string   $path_full          Full path
     * @param      string   $path_extension     Path extension (if it is a file)
     * @param      boolean  $path_is_dir        True if path is a directory
     */
    final public function setPath(string $path_full, string $path_extension, bool $path_is_dir): bool
    {
        $this->path_full      = $path_full;
        $this->path_extension = $path_extension;
        $this->path_is_dir    = $path_is_dir;

        $this->success->path($path_full);
        $this->warning->path($path_full);
        $this->error->path($path_full);

        return true;
    }

    /// @name Fix methods
    //@{
    /**
     * Called when starting to fix module.
     */
    public function openModule(): ?bool
    {
        return null;
    }

    /**
     * Called when open a directory to fix.
     */
    public function openDirectory(): ?bool
    {
        return null;
    }

    /**
     * Called when open a file to fix.
     */
    public function openFile(): ?bool
    {
        return null;
    }

    /**
     * Called when read content of a file to fix.
     *
     * Content is shared from action to another.
     * If an action erase content, fix is stopped.
     * If you want to erase a content you must erase
     * the file on action openDirectory.
     *
     * @param      string $content        File content
     */
    public function readFile(string &$content): ?bool
    {
        return null;
    }

    /**
     * Called when close a file to fix.
     */
    public function closeFile(): ?bool
    {
        return null;
    }

    /**
     * Called when close a module to fix.
     */
    public function closeModule(): ?bool
    {
        return null;
    }
    //@}
}
