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
 * This is the absract action class.
 * 
 * Action class must extends class ImproveAction.
 * Call 'create' function with child class name through behavior,
 * If your class signature is myClass extends ImproveAction, do
 * $core->addBehavior('ImproveAddAction'), ['myClass', 'create']);
 * then function init() of your class wil be called.
 * One class must manage only one action.
 */
abstract class ImproveAction
{
    protected $core;
    protected $type = '';
    protected $module = [];

    private static $notice = [];
    private $preferences = [];
    private $properties = [
        'id' => '',
        'name' => '',
        'desc' => '',
        'priority' => 500,
        'config' => false, //mixed bool for internal, string for ext url
        'types' => ['plugin']
    ];

    final public function __construct(dcCore $core)
    {
        $this->core = $core;

        self::$notice[get_called_class()] = ['error' => [], 'warning' => []];

        $pref = @unserialize($core->blog->settings->improve->get('preferences_' . get_called_class()));
        $this->preferences = is_array($pref) ? $pref : [];

        $this->init();

        // can overload priority by settings
        if (1 < ($p = (int) $core->blog->settings->improve->get('priority_'. get_called_class()))) {
            $this->priority = $p;
        }
    }

    final protected static function notice(string $message, bool $is_error = true)
    {
        if (!empty($message)) {
            self::$notice[get_called_class()][$is_error ? 'error' : 'warning'][] = $message;
        }
    }

    final public static function hasNotice(bool $error = true): bool
    {
        return !empty(self::$notice[get_called_class()][$error ? 'error' : 'warning']);
    }

    final public static function getNotice(bool $error = true): array
    {
        return self::$notice[get_called_class()][$error ? 'error' : 'warning'];
    }

    final public function __get(string $property)
    {
        return $this->getProperty($property);
    }

    final public function getProperty(string $property)
    {
        return $this->properties[$property] ?? null;
    }

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

    final protected function getPreference(string $preference)
    {
        return $this->preferences[$preference] ?? null;
    }

    final protected function setPreferences($preference, $value = null)
    {
        $preferences = is_array($preference) ? $preference : [$preference => $value];
        foreach($preferences as $k => $v) {
            $this->preferences[$k] = $v;
        }
        return true;
    }

    final protected function redirect(string $url)
    {
        $this->core->blog->settings->improve->put(
            'preferences_' . get_called_class(), 
            serialize($this->preferences), 
            'string', 
            null, 
            true, 
            true
        );
        $this->core->blog->triggerBlog();
        dcPage::addSuccessNotice(__('Configuration successfully updated.'));
        http::redirect($url);
    }

    abstract protected function init(): bool;

    abstract public function isConfigured(): bool;

    public static function create(arrayObject $o, dcCore $core)
    {
        $c = get_called_class();
        $o->append(new $c($core));
    }

    public function configure(string $redirect_url): ?string
    {
        return null;
    }

    public function openModule(string $module_type, array $module_info): ?bool
    {
        $this->type = $module_type;
        $this->module = $module_info;

        return null;
    }

    public function openDirectory(string $path): ?bool
    {
        return null;
    }

    public function openFile(string $path, string $extension): ?bool
    {
        return null;
    }

    public function readFile(string $path, string $extension, string &$content): ?bool
    {
        return null;
    }

    public function closeFile(string $path, string $extension): ?bool
    {
        return null;
    }

    public function closeModule(string $module_type, array $module_info): ?bool
    {
        return null;
    }
}