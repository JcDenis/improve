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
 * @brief Plugin improve action class
 *
 * Action class must extends class ImproveAction.
 * If your class signature is myActionClass extends ImproveAction, 
 * do $core->addBehavior('ImproveAddAction'), ['myClass', 'create']);
 * yoru action class is automatically created, 
 * then function init() of your class wil be called.
 * One class must manage only one action.
 *
 * @package Plugin_improve
 * @subpackage Action
 *
 * @copyright Jean-Christian Denis
 * @copyright GPL-2.0-only
 */
abstract class ImproveAction
{
    protected $core;
    protected $module = [];
    protected $path_full = '';
    protected $path_extension = '';
    protected $path_is_dir = null;

    private $logs = ['success' => [], 'warning' => [], 'error' => []];
    private $settings = [];
    private $properties = [
        'id' => '',
        'name' => '',
        'desc' => '',
        'priority' => 500,
        'config' => false, //mixed bool for internal, string for ext url
        'types' => ['plugin']
    ];

    /**
     * ImproveAction constructor inits properpties and settings of a child class.
     * 
     * @param      string  $core    dcCore instance
     */
    final public function __construct(dcCore $core)
    {
        $this->core = $core;

        $settings = @unserialize($core->blog->settings->improve->get('settings_' . get_called_class()));
        $this->settings = is_array($settings) ? $settings : [];

        $this->init();

        // can overload priority by settings
        if (1 < ($p = (int) $core->blog->settings->improve->get('priority_'. get_called_class()))) {
            $this->priority = $p;
        }
    }

    /**
     * Helper to create an instance of a ImproveAction child class.
     *
     * @param      string  $o       ArrayObject of actions list
     * @param      string  $core    dcCore instance
     */
    public static function create(arrayObject $o, dcCore $core)
    {
        $c = get_called_class();
        $o->append(new $c($core));
    }

    /**
     * Action initialisation function.
     * 
     * It's called when an instance of ImproveAction child class is created. 
     * Usefull to setup action class.
     * 
     * @return     bool  True if initialisation is ok.
     */
    abstract protected function init(): bool;

    /// @name Properties methods
    //@{
    /**
     * @see getProperty();
     */
    final public function __get(string $property)
    {
        return $this->getProperty($property);
    }

    /**
     * Get a definition property of action class
     * 
     * @return     mixed  A property of action definition.
     */
    final public function getProperty(string $property)
    {
        return $this->properties[$property] ?? null;
    }

    /**
     * Set a definition property of action class
     * 
     * Property can be:
     *  - id :          action id
     *  - name :        action short name
     *  - desc :        action short description,
     *  - priority :    order of execution of this action
     *  - config :      as configuration gui, false = none, true = internal, string = ext url
     *  - types :       array of supported type of module, can : be plugins and/or themes
     * 
     * @param      mixed  $property     one or more definition
     * @param      dtring $value        value for a single property
     * 
     * @return     mixed  A property of action definition.
     */
    final protected function setProperties($property, $value = null): bool
    {
        $properties = is_array($property) ? $property : [$property => $value];
        foreach($properties as $k => $v) {
            if (isset($this->properties[$k])) {
                if ($k == 'types' && !is_array($v)) {
                    $v = [$v];
                }
                $this->properties[$k] = $v;
            }
        }
        return true;
    }
    //@}

    /// @name Settings methods
    //@{
    /**
     * Get a settings of action class
     * 
     * @param      string $setting     a settings id
     * 
     * @return     mixed  A setting of action.
     */
    final protected function getSetting(string $setting)
    {
        return $this->settings[$setting] ?? null;
    }

    /**
     * Set one or more setting of action class
     * 
     * @param      mixed  $settings     one or more settings
     * @param      string  $value        value for a single setting
     * 
     * @return     mixed  A setting of action.
     */
    final protected function setSettings($settings, $value = null)
    {
        $settings = is_array($settings) ? $settings : [$setting => $value];
        foreach($settings as $k => $v) {
            $this->settings[$k] = $v;
        }
        return true;
    }

    /**
     * Redirection after settings update
     * 
     * This save settings update before redirect.
     * 
     * @param      string $url      redirect url after settings update
     */
    final protected function redirect(string $url)
    {
        $this->core->blog->settings->improve->put(
            'settings_' . get_called_class(), 
            serialize($this->settings), 
            'string', 
            null, 
            true, 
            true
        );
        $this->core->blog->triggerBlog();
        dcPage::addSuccessNotice(__('Configuration successfully updated.'));
        http::redirect($url);
    }

    /**
     * Check if action class is well configured
     * 
     * @return  boolean     True if class action is well configured
     */
    abstract public function isConfigured(): bool;

    /**
     * Get configuraton gui
     * 
     * If action class uses internal configuration, 
     * it must share here html form content of its settings.
     * It must not use enclose bloc "form" nor button "save".
     * This function is also called to redirect form 
     * after validation with $this->redirect($url);
     * 
     * @param      string $url     post form redirect url
     * 
     * @return     mixed  A setting of action.
     */
    public function configure(string $url): ?string
    {
        return null;
    }
    //@}

    /**
     * Set in class var current module definitions.
     * 
     * @see Improve::sanitizeModule()
     * 
     * @param      array $module      Full array of module definitons
     */
    final public function setModule(array $module)
    {
        $this->module = $module;
    }

    /**
     * Set in class var current path definitons.
     * 
     * @param      string $path_full        Full path
     * @param      string $path_extension   Path extension (if it is a file)
     * @param      string $path_is_dir      True if path is a directory
     */
    final public function setPath(string $path_full, string $path_extension, bool $path_is_dir)
    {
        $this->path_full = $path_full;
        $this->path_extension = $path_extension;
        $this->path_is_dir = $path_is_dir;
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

    /// @name Logs methods
    //@{
    /**
     * Set an action log.
     * 
     * Log must be use every time an action something happen.
     * 
     * @param      string $type        type of message, can be error, warning, succes
     * @param      string $message     message to log
     * 
     * @return     boolean  True if message is logged.
     */
    final public function setLog(string $type, string $message): bool
    {
        if (empty($this->path_full) || !array_key_exists($type, $this->logs)) {
            return false;
        }
        $this->logs[$type][$this->path_full][] = $message;
        return true;
    }

    /**
     * Check if action class has log of given type.
     * 
     * @param      string $type        type of message, can be error, warning, succes
     * 
     * @return     boolean  True if messages exist.
     */
    final public function hasLog(string $type): bool
    {
        return array_key_exists($type, $this->logs) && !empty($this->logs[$type]);
    }

    /**
     * Get action logs.
     * 
     * @param      mixed $type        type of message, can be error, warning, succes
     * 
     * @return     array  Arry of given type of log or all if type is null
     */
    final public function getLogs($type = null): array
    {
        if (null === $type) {
            return $this->logs;
        }
        if (empty($this->path_full) 
            || !array_key_exists($type, $this->logs)
            || !array_key_exists($this->path_full, $this->logs[$type])
        ) {
            return [];
        }
        return $this->logs[$type][$this->path_full];
    }

    /**
     * Set a log of type error.
     */
    final public function setError(string $message)
    {
        $this->setLog('error', $message);
    }

    /**
     * Check logs of type error exists.
     */
    final public function hasError(): bool
    {
        return !empty($this->getLogs('error'));
    }

    /**
     * Get logs of type error.
     */
    final public function getErrors(): array
    {
        return $this->getLogs('error');
    }

    /**
     * Set a log of type warning.
     */
    final public function setWarning(string $message)
    {
        $this->setLog('warning', $message);
    }

    /**
     * Check logs of type error warnings.
     */
    final public function hasWarning(): bool
    {
        return !empty($this->getLogs('warning'));
    }

    /**
     * Get logs of type warning.
     */
    final public function getWarnings(): array
    {
        return $this->getLogs('warning');
    }

    /**
     * Set a log of type success.
     */
    final public function setSuccess(string $message)
    {
        $this->setLog('success', $message);
    }

    /**
     * Check logs of type error success.
     */
    final public function hasSuccess(): bool
    {
        return !empty($this->getLogs('success'));
    }

    /**
     * Get logs of type success.
     */
    final public function getSuccess(): array
    {
        return $this->getLogs('success');
    }
    //@}
}