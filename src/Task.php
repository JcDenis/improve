<?php

declare(strict_types=1);

namespace Dotclear\Plugin\improve;

use Dotclear\Module\ModuleDefine;
use Dotclear\Helper\Network\Http;

/**
 * @brief       improve task helper.
 * @ingroup     improve
 *
 * Task MUST extends this class.
 *
 * @author      Jean-Christian Denis
 * @copyright   GPL-2.0 https://www.gnu.org/licenses/gpl-2.0.html
 */
abstract class Task
{
    /**
     * Task descriptor instance.
     *
     * @var     TaskDescriptor  $properties
     */
    public readonly TaskDescriptor $properties;

    /**
     * Task success messages instance.
     *
     * @var     TaskMessages    $success
     */
    public readonly TaskMessages $success;

    /**
     * Task warning messages instance.
     *
     * @var     TaskMessages    $warning
     */
    public readonly TaskMessages $warning;

    /**
     * Task error messages instance.
     *
     * @var     TaskMessages    $error
     */
    public readonly TaskMessages $error;

    /**
     * Task settings instance.
     *
     * @var     TaskSettings    $settings
     */
    protected readonly TaskSettings $settings;

    /**
     * Current module.
     *
     * @var     ModuleDefine    $module
     */
    protected ModuleDefine $module;

    /**
     * Is disabled task.
     *
     * @var     bool    $disabled
     */
    private bool $disabled = false;

    /**
     * Current full path.
     *
     * @var     string  $path_full
     */
    protected string $path_full = '';

    /**
     * Current file extension.
     *
     * @var     string  $path_extension
     */
    protected string $path_extension = '';

    /**
     * Current path is directory.
     *
     * @var     null|bool   $path_is_dir
     */
    protected ?bool $path_is_dir = null;

    /**
     * Task constructor inits properties and settings of a child class.
     */
    final public function __construct()
    {
        $this->success    = new TaskMessages();
        $this->warning    = new TaskMessages();
        $this->error      = new TaskMessages();
        $this->properties = $this->getProperties();
        $this->settings   = new TaskSettings($this->properties->id);
        $this->module     = new ModuleDefine('undefined');

        $this->init();
    }

    /**
     * Get task description.
     *
     * @return  TaskDescriptor  The task description
     */
    abstract protected function getProperties(): TaskDescriptor;

    /**
     * Task initialisation function.
     *
     * Called when Task insatnce is created.
     *
     * @return  bool    True if initialisation is ok.
     */
    abstract protected function init(): bool;

    /**
     * Get a setting.
     *
     * @param   string  $key    The setting ID
     *
     * @return  mixed   The setting value.
     */
    final public function get(string $key)
    {
        return $this->settings->get($key);
    }

    /**
     * Set task as disabled.
     */
    final public function disable(): void
    {
        $this->disabled = true;
    }

    /**
     * Check if task is disabled.
     *
     * @return  bool    True on disabled
     */
    final public function isDisabled(): bool
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
     * Check if task is well configured
     *
     * @return  bool    True on well configured
     */
    abstract public function isConfigured(): bool;

    /**
     * Get task configuration page header.
     *
     * @return  string  Headers
     */
    public function header(): ?string
    {
        return null;
    }

    /**
     * Get configuraton gui.
     *
     * If task class uses internal configuration,
     * it must share here html form content of its settings.
     * It must not use enclose bloc "form" nor button "save".
     * This function must redirect form
     * after validation with $this->redirect($url);
     *
     * @param   string  $url    post form redirect url
     *
     * @return  string  The configuration form
     */
    public function configure(string $url): string
    {
        return '';
    }

    /**
     * Set in class var current module definitions.
     *
     * @see     Improve::sanitizeModule()
     *
     * @param   ModuleDefine    $module     Module definitons
     */
    final public function setModule(ModuleDefine $module): bool
    {
        $this->module = $module;

        return true;
    }

    /**
     * Set in class var current path definitons.
     *
     * @param   string  $path_full          Full path
     * @param   string  $path_extension     Path extension (if it is a file)
     * @param   bool    $path_is_dir        True if path is a directory
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
     * Content is shared from task to another.
     * If an task erase content, fix is stopped.
     * If you want to erase a content you must erase
     * the file on action openDirectory.
     *
     * @param   string  $content    File content
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
