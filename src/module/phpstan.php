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

namespace Dotclear\Plugin\improve\Module;

/* improve */
use Dotclear\Plugin\improve\Action;
use Dotclear\Plugin\improve\My;

/* dotclear */
use dcCore;
use dcPage;
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

/* clearbricks */
use html;
use path;

/* php */
use Exception;

/**
 * Improve action module PHPStan
 */
class phpstan extends Action
{
    /** @var boolean User pref to use colored synthax */
    protected static $user_ui_colorsyntax = false;

    /** @var string User pref for colored synthax theme */
    protected static $user_ui_colorsyntax_theme = 'default';

    /** @var integer Settings phpstan run level */
    private $run_level = 5;

    /** @var string Settings phpstan ignored vars */
    private $ignored_vars = '';

    /** @var string Settings PHP executable path */
    private $phpexe_path = '';

    protected function init(): bool
    {
        $this->setProperties([
            'id'           => 'phpstan',
            'name'         => __('PHPStan'),
            'description'  => __('Analyse php code using PHPStan'),
            'priority'     => 910,
            'configurator' => true,
            'types'        => ['plugin'],
        ]);

        $this->getPhpPath();

        $run_level       = $this->getSetting('run_level');
        $this->run_level = is_int($run_level) ? $run_level : 5;

        $ignored_vars       = $this->getSetting('ignored_vars');
        $this->ignored_vars = is_string($ignored_vars) ? $ignored_vars : '';

        dcCore::app()->auth->user_prefs->addWorkspace('interface');
        self::$user_ui_colorsyntax       = dcCore::app()->auth->user_prefs->get('interface')->get('colorsyntax');
        self::$user_ui_colorsyntax_theme = dcCore::app()->auth->user_prefs->get('interface')->get('colorsyntax_theme');

        return true;
    }

    public function isConfigured(): bool
    {
        return !dcCore::app()->blog->settings->get(My::id())->get('nodetails');
    }

    public function header(): ?string
    {
        if (self::$user_ui_colorsyntax) {
            return dcPage::jsLoadCodeMirror(self::$user_ui_colorsyntax_theme);
        }

        return null;
    }

    public function configure($url): ?string
    {
        if (!empty($_POST['save'])) {
            $this->setSettings([
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
                    (new Label(__('Root directory of PHP executable:')))->for('phpexe_path'),
                    (new Input('phpexe_path'))->size(65)->maxlenght(255)->value($this->phpexe_path),
                ]),
                (new Note())->text(__('If this module does not work you can try to put here directory to php executable (without executable file name).'))->class('form-note'),
            ]),
            (new Fieldset())->class('fieldset')->legend((new Legend(__('Adjustments'))))->fields([
                // run_level
                (new Para())->items([
                    (new Label(__('Level:')))->for('run_level')->class('classic'),
                    (new Number('run_level'))->min(0)->max(50)->value($this->run_level),
                ]),
                // ignored_vars
                (new Para())->items([
                    (new Label(__('List of ignored variables:')))->for('ignored_vars'),
                    (new Input('ignored_vars'))->size(65)->maxlenght(255)->value($this->ignored_vars),
                ]),
                (new Note())->text(
                    sprintf(__('If you have errors like "%s", you can add this var here. Use ; as separator and do not put $ ahead.'), 'Variable $var might not be defined') .
                    ' ' . __('For exemple: var;_othervar;avar') . '<br />' . __('Some variables like core, _menu, are already set in ignored list.')
                )->class('form-note'),
                // ignored_default
                (new Para())->items([
                    (new Checkbox('ignored_default', !empty($this->getSetting('ignored_default'))))->value(1),
                    (new Label(__('Do not use rules from default ignored errors list.'), Label::OUTSIDE_LABEL_AFTER))->for('ignored_default')->class('classic'),
                ]),
                (new Note())->text(__('See ignored errors from configuration file below.'))->class('form-note'),
                // split_report
                (new Para())->items([
                    (new Checkbox('split_report', !empty($this->getSetting('split_report'))))->value(1),
                    (new Label(__('Split report by file rather than all in the end.'), Label::OUTSIDE_LABEL_AFTER))->for('split_report')->class('classic'),
                ]),
                (new Note())->text(__('Enable this can cause timeout.'))->class('form-note'),
                // clear_cache
                (new Para())->items([
                    (new Checkbox('clear_cache', !empty($this->getSetting('clear_cache'))))->value(1),
                    (new Label(__('Clear result cache before each analizes.'), Label::OUTSIDE_LABEL_AFTER))->for('clear_cache')->class('classic'),
                ]),
                (new Note())->text(__('Enable this can cause timeout.'))->class('form-note'),
            ]),
            (new Fieldset())->class('fieldset')->legend((new Legend(__('Bootstrap'))))->fields([
                // file_content
                (new Para())->items([
                    (new Label(__('PHPStan configuration file:')))->for('file_content'),
                    (new Textarea('file_content', html::escapeHTML($content)))->class('maximal')->cols(120)->rows(14)->extra('readonly="true"'),
                ]),
            ]),
        ])->render() . (
            !self::$user_ui_colorsyntax ? '' :
            dcPage::jsModuleLoad(My::id() . '/inc/module/phpstan/phpstan.improve.js') .
            dcPage::jsRunCodeMirror('editor', 'file_content', 'dotclear', self::$user_ui_colorsyntax_theme)
        );
    }

    public function openModule(): bool
    {
        if (!$this->writeConf()) {
            $this->setError(__('Failed to write phpstan configuration'));

            return false;
        }

        return true;
    }

    public function closeFile(): ?bool
    {
        if (!$this->getSetting('split_report')
            || !in_array($this->path_extension, ['php', 'in'])
        ) {
            return null;
        }

        $clear = $this->getSetting('clear_cache') ? $this->execClear($this->path_full) : true;

        return $clear && $this->execFixer($this->path_full);
    }

    public function closeModule(): ?bool
    {
        if ($this->getSetting('split_report')) {
            return null;
        }
        if ($this->hasError()) {
            return false;
        }

        $clear = $this->getSetting('clear_cache') ? $this->execClear() : true;

        return $clear && $this->execFixer();
    }

    private function execClear(string $path = null): bool
    {
        if (!empty($path)) {
            $path .= ' ';
        }

        return $this->execCmd(sprintf(
            '%sphp %s/phpstan/libs/phpstan.phar clear-result-cache',
            $this->phpexe_path,
            __DIR__
        ), true);
    }

    private function execFixer(string $path = null): bool
    {
        if (!empty($path)) {
            $path .= ' ';
        }

        return $this->execCmd(sprintf(
            '%sphp %s/phpstan/libs/phpstan.phar analyse ' . $path . '--configuration=%s',
            $this->phpexe_path,
            __DIR__,
            DC_VAR . '/phpstan.neon'
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
                $this->setSuccess($from_clear ? __('Cache cleared') : __('No errors found'));
            } else {
                $this->setWarning(sprintf('<pre>%s</pre>', implode('<br />', $output)));
            }

            return true;
        } catch (Exception $e) {
            $this->setError(__('Failed to run phpstan'));

            return false;
        }
    }

    /**
     * Get php executable path
     */
    private function getPhpPath(): void
    {
        $phpexe_path = $this->getSetting('phpexe_path');
        if (!is_string($phpexe_path)) {
            $phpexe_path = '';
        }
        if (empty($phpexe_path) && !empty(PHP_BINDIR)) {
            $phpexe_path = PHP_BINDIR;
        }
        $phpexe_path = (string) path::real($phpexe_path);
        if (!empty($phpexe_path)) {
            $phpexe_path .= '/';
        }
        $this->phpexe_path = $phpexe_path;
    }

    private function writeConf(): bool
    {
        $full    = $this->getSetting('ignored_default') ? '' : 'full.';
        $content = str_replace(
            [
                '%LEVEL%',
                '%MODULE_ROOT%',
                '%DC_ROOT%',
                '%BOOTSTRAP_ROOT%',
            ],
            [
                $this->run_level,
                (string) path::real($this->module->get('root'), false),
                (string) path::real(DC_ROOT, false),
                (string) path::real(__DIR__ . '/phpstan', false),
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

        return (bool) file_put_contents(DC_VAR . '/phpstan.neon', $content);
    }
}
