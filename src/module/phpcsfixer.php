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

use dcCore;
use dcPage;
use Dotclear\Helper\File\Path;
use Dotclear\Helper\Html\Form\{
    Div,
    Fieldset,
    Input,
    Label,
    Legend,
    Note,
    Para,
    Textarea
};
use Dotclear\Helper\Html\Html;
use Dotclear\Plugin\improve\{
    Action,
    My
};
use Exception;

/**
 * Improve action module PHP CS Fixer
 */
class phpcsfixer extends Action
{
    /** @var array<int,string> Type of runtime errors */
    protected static $errors = [
        0  => 'OK.',
        1  => 'General error (or PHP minimal requirement not matched).',
        4  => 'Some files have invalid syntax (only in dry-run mode).',
        8  => 'Some files need fixing (only in dry-run mode).',
        16 => 'Configuration error of the application.',
        32 => 'Configuration error of a Fixer.',
        64 => 'Exception raised within the application',
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
            'types'        => ['plugin', 'theme'],
        ]);

        $this->getPhpPath();

        dcCore::app()->auth->user_prefs->addWorkspace('interface');
        self::$user_ui_colorsyntax       = dcCore::app()->auth->user_prefs->get('interface')->get('colorsyntax');
        self::$user_ui_colorsyntax_theme = dcCore::app()->auth->user_prefs->get('interface')->get('colorsyntax_theme');

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
                'phpexe_path' => !empty($_POST['phpexe_path']) ? $_POST['phpexe_path'] : '',
            ]);
            $this->redirect($url);
        }
        $content = (string) file_get_contents(__DIR__ . '/phpcsfixer/phpcsfixer.rules.php');

        return (new Div())->items([
            (new Fieldset())->class('fieldset')->legend((new Legend(__('Root'))))->fields([
                // phpexe_path
                (new Para())->items([
                    (new Label(__('Root directory of PHP executable:'), Label::OUTSIDE_LABEL_BEFORE))->for('phpexe_path'),
                    (new Input('phpexe_path'))->size(65)->maxlenght(255)->value($this->phpexe_path),
                ]),
                (new Note())->text(__('If this module does not work you can try to put here directory to php executable (without executable file name).'))->class('form-note'),
            ]),
            (new Fieldset())->class('fieldset')->legend((new Legend(__('Bootstrap'))))->fields([
                // file_content
                (new Para())->items([
                    (new Label(__('PHP CS Fixer configuration file:'), Label::OUTSIDE_LABEL_BEFORE))->for('file_content'),
                    (new Textarea('file_content', Html::escapeHTML($content)))->class('maximal')->cols(120)->rows(14)->readonly(true),
                ]),
            ]),
        ])->render() . (
            !self::$user_ui_colorsyntax ? '' :
            dcPage::jsModuleLoad(My::id() . '/inc/module/phpcsfixer/phpcsfixer.improve.js') .
            dcPage::jsRunCodeMirror('editor', 'file_content', 'dotclear', self::$user_ui_colorsyntax_theme)
        );
    }

    public function closeModule(): ?bool
    {
        $command = sprintf(
            '%sphp %s/phpcsfixer/libs/php-cs-fixer.phar fix %s --config=%s/phpcsfixer/phpcsfixer.rules.php --using-cache=no',
            $this->phpexe_path,
            __DIR__,
            $this->module->get('root'),
            __DIR__
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
        $phpexe_path = (string) Path::real($phpexe_path);
        if (!empty($phpexe_path)) {
            $phpexe_path .= '/';
        }
        $this->phpexe_path = $phpexe_path;
    }
}
