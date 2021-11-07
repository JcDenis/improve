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
class ImproveActionPhpstan extends ImproveAction
{
    /** @var boolean User pref to use colored synthax */
    protected static $user_ui_colorsyntax = false;

    /** @var string User pref for colored synthax theme */
    protected static $user_ui_colorsyntax_theme = 'default';

    protected function init(): bool
    {
        $this->setProperties([
            'id'       => 'phpstan',
            'name'     => __('PHPStan'),
            'desc'     => __('Analyse php code using PHPStan'),
            'priority' => 910,
            'config'   => true,
            'types'    => ['plugin']
        ]);

        $this->core->auth->user_prefs->addWorkspace('interface');
        self::$user_ui_colorsyntax       = $this->core->auth->user_prefs->interface->colorsyntax;
        self::$user_ui_colorsyntax_theme = $this->core->auth->user_prefs->interface->colorsyntax_theme;

        return true;
    }

    public function isConfigured(): bool
    {
        return true;
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
                'phpexe_path'  => (!empty($_POST['phpexe_path']) ? $_POST['phpexe_path'] : ''),
                'run_level'    => (int) $_POST['run_level'],
                'ignored_vars' => (!empty($_POST['ignored_vars']) ? $_POST['ignored_vars'] : ''),
                'split_report' => !empty($_POST['split_report'])
            ]);
            $this->redirect($url);
        }
        $content = (string) file_get_contents(dirname(__FILE__) . '/libs/dc.phpstan.rules.conf');

        return
        '<p class="info">' . __('You must enable improve details to view analyse results !') . '</p>' .
        '<p><label class="classic" for="phpexe_path">' .
        __('Root directory of PHP executable:') . '<br />' .
        form::field('phpexe_path', 160, 255, $this->getSetting('phpexe_path')) . '</label>' .
        '</p>' .
        '<p class="form-note">' .
            __('If this module does not work you can try to put here directory to php executable (without executable file name).') .
        ' C:\path_to\php</p>' .
        '<p><label class="classic" for="run_level">' . __('Level:') . ' </label>' .
        form::number('run_level', ['min' => 0, 'max' => 9, 'default' => (int) $this->getSetting('run_level')]) . '</p>' .
        '<p><label class="classic" for="ignored_vars">' .
        __('List of ignored variables:') . '<br />' .
        form::field('ignored_vars', 160, 255, $this->getSetting('ignored_vars')) . '</label>' .
        '</p>' .
        '<p class="form-note">' . sprintf(
            __('If you have errors like "%s", you can add this var here. Use ; as separator and do not put $ ahead.'),
            'Variable $var might not be defined'
        ) . ' ' . __('For exemple: var;_othervar;avar') . '<br />' . __('Some variables like core, _menu, are already set in ignored list.') . '</p>' .
        '<p><label class="classic" for="split_report">' .
        form::checkbox('split_report', 1, $this->getSetting('split_report')) .
        __('Split report by file rather than all in the end.') . '</label></p>' .
        '<p class="form-note">' . __('Enable this can cause timeout.') . '</p>' .

        '<p><label for="file_content">' . __('PHPStan configuration file:') . '</strong></label></p>' .
        '<p>' . form::textarea('file_content', 120, 14, [
            'default'    => html::escapeHTML($content),
            'class'      => 'maximal',
            'extra_html' => 'readonly="true"'
        ]) . '</p>' .
        (
            !self::$user_ui_colorsyntax ? '' :
            dcPage::jsLoad(dcPage::getPF('improve/inc/lib.improve.action.phpstan.js')) .
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

        return $this->execFixer($this->path_full);
    }

    public function closeModule(): ?bool
    {
        if ($this->getSetting('split_report')) {
            return null;
        }
        if ($this->hasError()) {
            return false;
        }

        return $this->execFixer();
    }

    private function execFixer(string $path = null): bool
    {
        $phpexe_path = $this->getPhpPath();
        if (!empty($phpexe_path)) {
            $phpexe_path .= '/';
        }
        if (!empty($path)) {
            $path .= ' ';
        }

        $command = sprintf(
            '%sphp %s/libs/phpstan.phar analyse ' . $path . '--configuration=%s',
            $phpexe_path,
            dirname(__FILE__),
            DC_VAR . '/phpstan.neon'
        );

        try {
            exec($command, $output, $error);

            if (!empty($error) && empty($output)) {
                throw new Exception('oops');
            }
            if (count($output) < 4) {
                $this->setSuccess(__('No errors found'));
            } else {
                $this->setWarning(sprintf('<pre>%s</pre>', implode('<br />', $output)));
            }

            return true;
        } catch (Exception $e) {
            $this->setError(__('Failed to run phpstan'));

            return false;
        }
    }

    private function getPhpPath(): string
    {
        $phpexe_path = $this->getSetting('phpexe_path');
        if (empty($phpexe_path) && !empty(PHP_BINDIR)) {
            $phpexe_path = PHP_BINDIR;
        }

        return (string) path::real($phpexe_path);
    }

    private function writeConf(): bool
    {
        $content = str_replace(
            [
                '%LEVEL%',
                '%MODULE_ROOT%',
                '%DC_ROOT%',
                '%BOOTSTRAP_ROOT%'
            ],
            [
                (int) $this->getSetting('run_level'),
                $this->module['sroot'],
                DC_ROOT,
                dirname(__FILE__) . '/libs/'
            ],
            (string) file_get_contents(dirname(__FILE__) . '/libs/dc.phpstan.rules.conf')
        );

        $ignored = explode(';', $this->getSetting('ignored_vars'));
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
