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
use Dotclear\Helper\Html\Form\{
    Checkbox,
    Div,
    Fieldset,
    Label,
    Legend,
    Note,
    Para,
    Select,
    Textarea
};
use Dotclear\Helper\Html\Html;
use Dotclear\Plugin\improve\Action;
use Exception;

/**
 * Improve action module php header
 */
class phpheader extends Action
{
    /** @var string Exemple of header */
    private static $exemple = <<<EOF
        @brief %module_id%, a %module_type% for Dotclear 2

        @package Dotclear
        @subpackage \u%module_type%

        @author %module_author%

        @copyright %user_cn%
        @copyright GPL-2.0 https://www.gnu.org/licenses/gpl-2.0.html
        EOF;

    /** @var array<string> Allowed bloc replacement */
    private $bloc_wildcards = [
        '%year%',
        '%module_id%',
        '%module_name%',
        '%module_author%',
        '%module_type%',
        '%user_cn%',
        '%user_name%',
        '%user_email%',
        '%user_url%',
    ];

    /** @var array Allowed action for header */
    private $action_bloc = [];

    /** @var string Parsed bloc */
    private $bloc = '';

    /** @var boolean Stop parsing files */
    private $stop_scan = false;

    /** @var string Settings bloc content */
    private $bloc_content = '';

    protected function init(): bool
    {
        $this->setProperties([
            'id'           => 'phpheader',
            'name'         => __('PHP header'),
            'description'  => __('Add or remove phpdoc header bloc from php file'),
            'priority'     => 340,
            'configurator' => true,
            'types'        => ['plugin', 'theme'],
        ]);

        $this->action_bloc = [
            __('Do nothing')                       => 0,
            __('Add bloc if it does not exist')    => 'create',
            __('Add and overwrite bloc')           => 'overwrite',
            __('Overwrite bloc only if it exists') => 'replace',
            __('Remove existing bloc header')      => 'remove',
        ];

        $bloc_content       = $this->getSetting('bloc_content');
        $this->bloc_content = is_string($bloc_content) ? $bloc_content : '';

        return true;
    }

    public function isConfigured(): bool
    {
        return !empty($this->getSetting('bloc_action')) || !empty($this->getSetting('remove_old'));
    }

    public function configure($url): ?string
    {
        if (!empty($_POST['save'])) {
            $this->setSettings([
                'bloc_action'     => !empty($_POST['bloc_action']) ? $_POST['bloc_action'] : '',
                'bloc_content'    => !empty($_POST['bloc_content']) ? $_POST['bloc_content'] : '',
                'remove_old'      => !empty($_POST['remove_old']),
                'exclude_locales' => !empty($_POST['exclude_locales']),
            ]);
            $this->redirect($url);
        }

        return (new Div())->items([
            (new Fieldset())->class('fieldset')->legend((new Legend(__('Adjustments'))))->fields([
                // bloc_action
                (new Para())->items([
                    (new Label(__('Action:')))->for('bloc_action'),
                    (new Select('bloc_action'))->default($this->getSetting('bloc_action'))->items($this->action_bloc),
                ]),
                // remove_old
                (new Para())->items([
                    (new Checkbox('remove_old', !empty($this->getSetting('remove_old'))))->value(1),
                    (new Label(__('Remove old style bloc header (using #)'), Label::OUTSIDE_LABEL_AFTER))->for('remove_old')->class('classic'),
                ]),
                // exclude_locales
                (new Para())->items([
                    (new Checkbox('exclude_locales', !empty($this->getSetting('exclude_locales'))))->value(1),
                    (new Label(__('Do not add bloc to files from "locales" and "libs" folder'), Label::OUTSIDE_LABEL_AFTER))->for('exclude_locales')->class('classic'),
                ]),
            ]),
            (new Fieldset())->class('fieldset')->legend((new Legend(__('Contents'))))->fields([
                // bloc_content
                (new Para())->items([
                    (new Label(__('Bloc content:')))->for('bloc_content'),
                    (new Textarea('bloc_content', Html::escapeHTML($this->bloc_content)))->cols(120)->rows(10),
                ]),
                (new Note())->text(sprintf(
                    __('You can use wildcards %s'),
                    '%year%, %module_id%, %module_name%, %module_author%, %module_type%, %user_cn%, %user_name%, %user_email%, %user_url%'
                ))->class('form-note'),
                (new Note())->text(__('Do not put structural elements to the begining of lines.'))->class('form-note'),
                // exemple
                (new Para())->items([
                    (new Label(__('Exemple:')))->for('content_exemple'),
                    (new Textarea('content_exemple', Html::escapeHTML(self::$exemple)))->cols(120)->rows(10)->readonly(true),
                ]),
            ]),
        ])->render();
    }

    public function openModule(): ?bool
    {
        $bloc = trim($this->bloc_content);

        if (empty($bloc)) {
            $this->setWarning(__('bloc is empty'));

            return null;
        }

        $bloc = trim(str_replace("\r\n", "\n", $bloc));

        try {
            $this->bloc = (string) preg_replace_callback(
                // use \u in bloc content for first_upper_case
                '/(\\\u([a-z]{1}))/',
                function ($str) {
                    return ucfirst($str[2]);
                },
                str_replace(
                    $this->bloc_wildcards,
                    [
                        date('Y'),
                        $this->module->getId(),
                        $this->module->get('name'),
                        $this->module->get('author'),
                        $this->module->get('type'),
                        dcCore::app()->auth->getInfo('user_cn'),
                        dcCore::app()->auth->getinfo('user_name'),
                        dcCore::app()->auth->getInfo('user_email'),
                        dcCore::app()->auth->getInfo('user_url'),
                    ],
                    (string) $bloc
                )
            );
            $this->setSuccess(__('Prepare header info'));

            return null;
        } catch (Exception $e) {
            $this->setError(__('Failed to parse bloc'));

            return null;
        }
    }

    public function openDirectory(): ?bool
    {
        $skipped         = $this->stop_scan;
        $this->stop_scan = false;
        if (!empty($this->getSetting('exclude_locales')) && preg_match('/\/(locales|libs)(\/.*?|)$/', $this->path_full)) {
            if (!$skipped) {
                $this->setSuccess(__('Skip directory'));
            }
            $this->stop_scan = true;
        }

        return null;
    }

    public function readFile(&$content): ?bool
    {
        if ($this->stop_scan || $this->path_extension != 'php' || $this->hasError()) {
            return null;
        }

        if (!empty($this->getSetting('remove_old'))) {
            $content = $this->deleteOldBloc($content);
        }
        if (empty($this->getSetting('bloc_action'))) {
            return null;
        }
        $clean = $this->deleteDocBloc($content);
        if ($this->getSetting('bloc_action') == 'remove') {
            $content = $clean;

            return null;
        }
        if ($content != $clean && $this->getSetting('bloc_action') == 'create') {
            return null;
        }
        if ($content == $clean && $this->getSetting('bloc_action') == 'replace') {
            return null;
        }

        $content = $this->writeDocBloc($clean);

        return true;
    }

    /**
     * Write bloc content in file content
     *
     * @param  string $content Old content
     * @return string          New content
     */
    private function writeDocBloc(string $content): string
    {
        $res = preg_replace(
            '/^(\<\?php[\n|\r\n]+)/',
            '<?php' . "\n/**\n * " . str_replace("\n", "\n * ", trim($this->bloc)) . "\n */\n",
            $content,
            1,
            $count
        );
        if ($count && $res) {
            $res = str_replace("\n * \n", "\n *\n", $res);
            $this->setSuccess(__('Write new doc bloc content'));
        }

        return (string) $res;
    }

    /**
     * Delete bloc content in file content
     *
     * @param  string $content Old content
     * @return string          New content
     */
    private function deleteDocBloc(string $content): string
    {
        $res = preg_replace(
            '/^(\<\?php\s*[\n|\r\n]{0,1}\s*\/\*\*.*?\s*\*\/\s*[\n|\r\n]+)/msi',
            "<?php\n",
            $content,
            -1,
            $count
        );
        if ($count) {
            $this->setSuccess(__('Delete old doc bloc content'));
        }

        return (string) $res;
    }

    /**
     * Delete old style bloc content in file content
     *
     * @param  string $content Old content
     * @return string          New content
     */
    private function deleteOldBloc(string $content): string
    {
        $res = preg_replace(
            '/((# -- BEGIN LICENSE BLOCK ([-]+))(.*?)(# -- END LICENSE BLOCK ([-]+))([\n|\r\n]{1,}))/msi',
            '',
            $content,
            -1,
            $count
        );
        if ($count) {
            $this->setSuccess(__('Delete old style bloc content'));
        }

        return (string) $res;
    }
}
