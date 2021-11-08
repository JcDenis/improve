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
class ImproveActionPhpcsfixer extends ImproveAction
{
    /** @var array<int,string> Type of runtime errors */
    protected static $errors = [
        0  => 'OK.',
        1  => 'General error (or PHP minimal requirement not matched).',
        4  => 'Some files have invalid syntax (only in dry-run mode).',
        8  => 'Some files need fixing (only in dry-run mode).',
        16 => 'Configuration error of the application.',
        32 => 'Configuration error of a Fixer.',
        64 => 'Exception raised within the application'
    ];

    /** @var boolean User pref to use colored synthax */
    protected static $user_ui_colorsyntax = false;

    /** @var string User pref for colored synthax theme */
    protected static $user_ui_colorsyntax_theme = 'default';

    /** @var string Settings PHP executable path */
    private $phpexe_path = '';

    protected function init(): bool
    {
        $this->setProperties([
            'id'           => 'phpcsfixer',
            'name'         => __('PHP CS Fixer'),
            'description'  => __('Fix PSR coding style using Php CS Fixer'),
            'priority'     => 920,
            'configurator' => true,
            'types'        => ['plugin', 'theme']
        ]);

        $this->getPhpPath();

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
                'phpexe_path' => !empty($_POST['phpexe_path']) ? $_POST['phpexe_path'] : ''
            ]);
            $this->redirect($url);
        }
        $content = (string) file_get_contents(dirname(__FILE__) . '/libs/dc.phpcsfixer.rules.php');

        return
        '<p><label class="classic" for="phpexe_path">' .
        __('Root directory of PHP executable:') . '<br />' .
        form::field('phpexe_path', 160, 255, $this->phpexe_path) . '</label>' .
        '</p>' .
        '<p class="form-note">' .
            __('If this module does not work you can try to put here directory to php executable (without executable file name).') .
        ' C:\path_to\php</p>' .

        '<p><label for="file_content">' . __('PHP CS Fixer configuration file:') . '</strong></label></p>' .
        '<p>' . form::textarea('file_content', 120, 60, [
            'default'    => html::escapeHTML($content),
            'class'      => 'maximal',
            'extra_html' => 'readonly="true"'
        ]) . '</p>' .
        (
            !self::$user_ui_colorsyntax ? '' :
            dcPage::jsLoad(dcPage::getPF('improve/inc/lib.improve.action.phpcsfixer.js')) .
            dcPage::jsRunCodeMirror('editor', 'file_content', 'dotclear', self::$user_ui_colorsyntax_theme)
        );
    }

    public function closeModule(): ?bool
    {
        $command = sprintf(
            '%sphp %s/libs/php-cs-fixer.phar fix %s --config=%s/libs/dc.phpcsfixer.rules.php --using-cache=no',
            $this->phpexe_path,
            dirname(__FILE__),
            $this->module['sroot'],
            dirname(__FILE__)
        );

        try {
            exec($command, $output, $error);
            if (empty($output)) {
                if (isset(self::$errors[$error])) {
                    $this->setError(self::$errors[$error]);

                    return false;
                }

                throw new Exception('oops');
            }
            $this->setSuccess(sprintf('<pre>%s</pre>', implode('<br />', $output)));

            return true;
        } catch (Exception $e) {
            $this->setError(__('Failed to run php-cs-fixer'));

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
}
