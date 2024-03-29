<?php

declare(strict_types=1);

namespace Dotclear\Plugin\improve\Task;

use Dotclear\App;
use Dotclear\Core\Backend\Page;
use Dotclear\Helper\File\Path;
use Dotclear\Helper\Html\Form\{
    Checkbox,
    Div,
    Fieldset,
    Input,
    Label,
    Legend,
    Note,
    Number,
    Para,
    Textarea
};
use Dotclear\Helper\Html\Html;
use Dotclear\Plugin\improve\{
    Task,
    My,
    TaskDescriptor
};
use Exception;

/**
 * @brief       improve task: PHPstan class.
 * @ingroup     improve
 *
 * @author      Jean-Christian Denis
 * @copyright   GPL-2.0 https://www.gnu.org/licenses/gpl-2.0.html
 */
class PhpStan extends Task
{
    /**
     * User pref to use colored synthax.
     *
     * @var     bool    $user_ui_colorsyntax
     */
    protected static $user_ui_colorsyntax = false;

    /**
     * User pref for colored synthax theme.
     *
     * @var     string  $user_ui_colorsyntax_theme
     */
    protected static $user_ui_colorsyntax_theme = 'default';

    /**
     * Settings phpstan run level.
     *
     * @var     int     $run_level
     */
    private $run_level = 8;

    /**
     * Settings phpstan ignored vars.
     *
     * @var     string  $ignored_vars
     */
    private $ignored_vars = '';

    /**
     * Settings PHP executable path.
     *
     * @var     string  $phpexe_path
     */
    private $phpexe_path = '';

    protected function getProperties(): TaskDescriptor
    {
        return new TaskDescriptor(
            id: 'phpstan',
            name: __('PHPStan'),
            description: __('Analyse php code using PHPStan'),
            configurator: true,
            types: ['plugin'],
            priority: 910
        );
    }

    protected function init(): bool
    {
        $this->getPhpPath();

        $run_level       = $this->settings->get('run_level');
        $this->run_level = is_int($run_level) ? $run_level : 5;

        $ignored_vars       = $this->settings->get('ignored_vars');
        $this->ignored_vars = is_string($ignored_vars) ? $ignored_vars : '';

        //App::auth()->prefs()->addWorkspace('interface');
        self::$user_ui_colorsyntax       = App::auth()->prefs()->get('interface')->get('colorsyntax');
        self::$user_ui_colorsyntax_theme = App::auth()->prefs()->get('interface')->get('colorsyntax_theme');

        return true;
    }

    public function isConfigured(): bool
    {
        return !My::settings()->get('nodetails');
    }

    public function header(): ?string
    {
        if (self::$user_ui_colorsyntax) {
            return Page::jsLoadCodeMirror(self::$user_ui_colorsyntax_theme);
        }

        return null;
    }

    public function configure(string $url): string
    {
        if (!empty($_POST['save'])) {
            $this->settings->set([
                'phpexe_path'     => (!empty($_POST['phpexe_path']) ? $_POST['phpexe_path'] : ''),
                'run_level'       => (int) $_POST['run_level'],
                'ignored_vars'    => (!empty($_POST['ignored_vars']) ? $_POST['ignored_vars'] : ''),
                'ignored_default' => !empty($_POST['ignored_default']),
                'split_report'    => !empty($_POST['split_report']),
                'clear_cache'     => !empty($_POST['clear_cache']),
            ]);
            $this->redirect($url);
        }
        $content = (string) file_get_contents(__DIR__ . '/phpstan/phpstan.rules.full.conf');

        return (new Div())->items([
            (new Note())->text(__('You must enable improve details to view analyse results !'))->class('form-note'),
            (new Fieldset())->class('fieldset')->legend((new Legend(__('Root'))))->fields([
                // phpexe_path
                (new Para())->items([
                    (new Label(__('Root directory of PHP executable:'), Label::OUTSIDE_LABEL_BEFORE))->for('phpexe_path'),
                    (new Input('phpexe_path'))->size(65)->maxlenght(255)->value($this->phpexe_path),
                ]),
                (new Note())->text(__('If this module does not work you can try to put here directory to php executable (without executable file name).'))->class('form-note'),
            ]),
            (new Fieldset())->class('fieldset')->legend((new Legend(__('Adjustments'))))->fields([
                // run_level
                (new Para())->items([
                    (new Label(__('Level:'), Label::OUTSIDE_LABEL_BEFORE))->for('run_level')->class('classic'),
                    (new Number('run_level'))->min(0)->max(50)->value($this->run_level),
                ]),
                // ignored_vars
                (new Para())->items([
                    (new Label(__('List of ignored variables:'), Label::OUTSIDE_LABEL_BEFORE))->for('ignored_vars'),
                    (new Input('ignored_vars'))->size(65)->maxlenght(255)->value($this->ignored_vars),
                ]),
                (new Note())->text(
                    sprintf(__('If you have errors like "%s", you can add this var here. Use ; as separator and do not put $ ahead.'), 'Variable $var might not be defined') .
                    ' ' . __('For exemple: var;_othervar;avar') . '<br />' . __('Some variables like core, _menu, are already set in ignored list.')
                )->class('form-note'),
                // ignored_default
                (new Para())->items([
                    (new Checkbox('ignored_default', !empty($this->settings->get('ignored_default'))))->value(1),
                    (new Label(__('Do not use rules from default ignored errors list.'), Label::OUTSIDE_LABEL_AFTER))->for('ignored_default')->class('classic'),
                ]),
                (new Note())->text(__('See ignored errors from configuration file below.'))->class('form-note'),
                // split_report
                (new Para())->items([
                    (new Checkbox('split_report', !empty($this->settings->get('split_report'))))->value(1),
                    (new Label(__('Split report by file rather than all in the end.'), Label::OUTSIDE_LABEL_AFTER))->for('split_report')->class('classic'),
                ]),
                (new Note())->text(__('Enable this can cause timeout.'))->class('form-note'),
                // clear_cache
                (new Para())->items([
                    (new Checkbox('clear_cache', !empty($this->settings->get('clear_cache'))))->value(1),
                    (new Label(__('Clear result cache before each analizes.'), Label::OUTSIDE_LABEL_AFTER))->for('clear_cache')->class('classic'),
                ]),
                (new Note())->text(__('Enable this can cause timeout.'))->class('form-note'),
            ]),
            (new Fieldset())->class('fieldset')->legend((new Legend(__('Bootstrap'))))->fields([
                // file_content
                (new Para())->items([
                    (new Label(__('PHPStan configuration file:'), Label::OUTSIDE_LABEL_BEFORE))->for('file_content'),
                    (new Textarea('file_content', Html::escapeHTML($content)))->class('maximal')->cols(120)->rows(14)->readonly(true),
                ]),
            ]),
        ])->render() . (
            !self::$user_ui_colorsyntax ? '' :
            My::jsLoad('/src/Task/phpstan/phpstan.improve.js') .
            Page::jsRunCodeMirror('editor', 'file_content', 'dotclear', self::$user_ui_colorsyntax_theme)
        );
    }

    public function openModule(): bool
    {
        if (!$this->writeConf()) {
            $this->error->add(__('Failed to write phpstan configuration'));

            return false;
        }

        return true;
    }

    public function closeFile(): ?bool
    {
        if (!$this->settings->get('split_report')
            || !in_array($this->path_extension, ['php', 'in'])
        ) {
            return null;
        }

        $clear = $this->settings->get('clear_cache') ? $this->execClear($this->path_full) : true;

        return $clear && $this->execFixer($this->path_full);
    }

    public function closeModule(): ?bool
    {
        if ($this->settings->get('split_report')) {
            return null;
        }
        if (!$this->error->empty()) {
            return false;
        }

        $clear = $this->settings->get('clear_cache') ? $this->execClear() : true;

        return $clear && $this->execFixer();
    }

    private function execClear(string $path = null): bool
    {
        if (!empty($path)) {
            $path .= ' ';
        }

        return $this->execCmd(sprintf(
            '%sphp %s/phpstan/libs/phpstan.phar clear-result-cache --configuration=%s',
            $this->phpexe_path,
            __DIR__,
            App::config()->varRoot() . '/phpstan.neon'
        ), true);
    }

    private function execFixer(string $path = null): bool
    {
        if (empty($path)) {
            $path = Path::real($this->module->get('root'));
        }

        return $this->execCmd(sprintf(
            '%sphp %s/phpstan/libs/phpstan.phar analyse ' . $path . ' --configuration=%s',
            $this->phpexe_path,
            __DIR__,
            App::config()->varRoot() . '/phpstan.neon'
        ));
    }

    private function execCmd(string $command, bool $from_clear = false): bool
    {
        try {
            exec($command, $output, $error);

            if (!empty($error) && empty($output)) {
                throw new Exception('oops');
            }
            if (count($output) < 4) {
                $this->success->add($from_clear ? __('Cache cleared') : __('No errors found'));
            } else {
                $this->warning->add(sprintf('<pre>%s</pre>', implode('<br />', $output)));
            }

            return true;
        } catch (Exception $e) {
            $this->error->add(__('Failed to run phpstan'));

            return false;
        }
    }

    /**
     * Get php executable path.
     */
    private function getPhpPath(): void
    {
        $phpexe_path = $this->settings->get('phpexe_path');
        if (!is_string($phpexe_path)) {
            $phpexe_path = '';
        }
        if (empty($phpexe_path) && !empty(PHP_BINDIR)) {
            $phpexe_path = PHP_BINDIR;
        }
        $phpexe_path = (string) Path::real($phpexe_path);
        if (!empty($phpexe_path)) {
            $phpexe_path .= '/';
        }
        $this->phpexe_path = $phpexe_path;
    }

    private function writeConf(): bool
    {
        $full    = $this->settings->get('ignored_default') ? '' : 'full.';
        $content = str_replace(
            [
                '%LEVEL%',
                '%MODULE_ROOT%',
                '%DC_ROOT%',
                '%CACHE_ROOT%',
                '%BOOTSTRAP_ROOT%',
                '%SCAN_DIRECTORIES%',
            ],
            [
                $this->run_level,
                (string) Path::real($this->module->get('root'), false),
                (string) Path::real(App::config()->dotclearRoot(), false),
                (string) Path::real(App::config()->cacheRoot(), false),
                (string) Path::real(__DIR__ . '/phpstan', false),
                $this->getScanDirectories(),
            ],
            (string) file_get_contents(__DIR__ . '/phpstan/phpstan.rules.' . $full . 'conf')
        );

        $ignored = explode(';', $this->ignored_vars);
        foreach ($ignored as $var) {
            $var = trim($var);
            if (empty($var)) {
                continue;
            }

            $content .= '    # $' . $var . ' variable may not be defined (globally)' . "\n" .
                '    - message: \'#Variable \$' . $var . ' might not be defined#\'' . "\n" .
                '      path: *' . "\n\n";
        }

        return (bool) file_put_contents(App::config()->varRoot() . '/phpstan.neon', $content);
    }

    private function getScanDirectories(): string
    {
        $ret = '';
        if ($this->module->get('type') == 'plugin') {
            $paths = explode(PATH_SEPARATOR, App::config()->pluginsRoot());
            foreach ($paths as $path) {
                $path = Path::real($path, false);
                if ($path !== false && $path != Path::real(App::config()->dotclearRoot() . DIRECTORY_SEPARATOR . 'plugins', false)) {
                    $ret .= '    - ' . $path . "\n";
                }
            }
        }

        return $ret;
    }
}
