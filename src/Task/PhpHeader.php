<?php

declare(strict_types=1);

namespace Dotclear\Plugin\improve\Task;

use Dotclear\App;
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
use Dotclear\Plugin\improve\{
    Task,
    TaskDescriptor
};
use Exception;

/**
 * @brief       improve task: header class.
 * @ingroup     improve
 *
 * @author      Jean-Christian Denis
 * @copyright   GPL-2.0 https://www.gnu.org/licenses/gpl-2.0.html
 */
class PhpHeader extends Task
{
    /**
     * Exemple of header.
     *
     * @var     string  $exemple
     */
    private static $exemple = <<<EOF
        @brief %module_id%, a %module_type% for Dotclear 2

        @package Dotclear
        @subpackage \u%module_type%

        @author %module_author%

        @copyright %user_cn%
        @copyright GPL-2.0 https://www.gnu.org/licenses/gpl-2.0.html
        EOF;

    /**
     * Allowed bloc replacement.
     *
     * @var     array<int, string>  bloc_wildcards
     */
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

    /**
     * Allowed action for header.
     *
     * @var     array<string, string>   $action_bloc
     */
    private $action_bloc = [];

    /**
     * Parsed bloc.
     *
     * @var     string  $bloc
     */
    private $bloc = '';

    /**
     * Stop parsing files.
     *
     * @var     bool    $stop_scan
     */
    private $stop_scan = false;

    /**
     * Settings bloc content.
     *
     * @var     string  $bloc_content
     */
    private $bloc_content = '';

    protected function getProperties(): TaskDescriptor
    {
        return new TaskDescriptor(
            id: 'phpheader',
            name: __('PHP header'),
            description: __('Add or remove phpdoc header bloc from php file'),
            configurator: true,
            types: ['plugin', 'theme'],
            priority: 340
        );
    }

    protected function init(): bool
    {
        $this->action_bloc = [
            __('Do nothing')                       => '',
            __('Add bloc if it does not exist')    => 'create',
            __('Add and overwrite bloc')           => 'overwrite',
            __('Overwrite bloc only if it exists') => 'replace',
            __('Remove existing bloc header')      => 'remove',
        ];

        $bloc_content       = $this->settings->get('bloc_content');
        $this->bloc_content = is_string($bloc_content) ? $bloc_content : '';

        return true;
    }

    public function isConfigured(): bool
    {
        return !empty($this->settings->get('bloc_action')) || !empty($this->settings->get('remove_old'));
    }

    public function configure(string $url): string
    {
        if (!empty($_POST['save'])) {
            $this->settings->set([
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
                    (new Label(__('Action:'), Label::OUTSIDE_LABEL_BEFORE))->for('bloc_action'),
                    (new Select('bloc_action'))->default($this->settings->get('bloc_action'))->items($this->action_bloc),
                ]),
                // remove_old
                (new Para())->items([
                    (new Checkbox('remove_old', !empty($this->settings->get('remove_old'))))->value(1),
                    (new Label(__('Remove old style bloc header (using #)'), Label::OUTSIDE_LABEL_AFTER))->for('remove_old')->class('classic'),
                ]),
                // exclude_locales
                (new Para())->items([
                    (new Checkbox('exclude_locales', !empty($this->settings->get('exclude_locales'))))->value(1),
                    (new Label(__('Do not add bloc to files from "locales" and "libs" folder'), Label::OUTSIDE_LABEL_AFTER))->for('exclude_locales')->class('classic'),
                ]),
            ]),
            (new Fieldset())->class('fieldset')->legend((new Legend(__('Contents'))))->fields([
                // bloc_content
                (new Para())->items([
                    (new Label(__('Bloc content:'), Label::OUTSIDE_LABEL_BEFORE))->for('bloc_content'),
                    (new Textarea('bloc_content', Html::escapeHTML($this->bloc_content)))->cols(120)->rows(10),
                ]),
                (new Note())->text(sprintf(
                    __('You can use wildcards %s'),
                    '%year%, %module_id%, %module_name%, %module_author%, %module_type%, %user_cn%, %user_name%, %user_email%, %user_url%'
                ))->class('form-note'),
                (new Note())->text(__('Do not put structural elements to the begining of lines.'))->class('form-note'),
                // exemple
                (new Para())->items([
                    (new Label(__('Exemple:'), Label::OUTSIDE_LABEL_BEFORE))->for('content_exemple'),
                    (new Textarea('content_exemple', Html::escapeHTML(self::$exemple)))->cols(120)->rows(10)->readonly(true),
                ]),
            ]),
        ])->render();
    }

    public function openModule(): ?bool
    {
        $bloc = trim($this->bloc_content);

        if (empty($bloc)) {
            $this->warning->add(__('bloc is empty'));

            return null;
        }

        $bloc = trim(str_replace("\r\n", "\n", $bloc));

        try {
            $this->bloc = (string) preg_replace_callback(
                // use \u in bloc content for first_upper_case
                '/(\\\u([a-z]{1}))/',
                fn ($str) => ucfirst($str[2]),
                str_replace(
                    $this->bloc_wildcards,
                    [
                        date('Y'),
                        $this->module->getId(),
                        (string) $this->module->get('name'),
                        (string) $this->module->get('author'),
                        (string) $this->module->get('type'),
                        (string) App::auth()->getInfo('user_cn'),
                        (string) App::auth()->getinfo('user_name'),
                        (string) App::auth()->getInfo('user_email'),
                        (string) App::auth()->getInfo('user_url'),
                    ],
                    (string) $bloc
                )
            );
            $this->success->add(__('Prepare header info'));

            return null;
        } catch (Exception $e) {
            $this->error->add(__('Failed to parse bloc'));

            return null;
        }
    }

    public function openDirectory(): ?bool
    {
        $skipped         = $this->stop_scan;
        $this->stop_scan = false;
        if (!empty($this->settings->get('exclude_locales')) && preg_match('/\/(locales|libs)(\/.*?|)$/', $this->path_full)) {
            if (!$skipped) {
                $this->success->add(__('Skip directory'));
            }
            $this->stop_scan = true;
        }

        return null;
    }

    public function readFile(&$content): ?bool
    {
        if ($this->stop_scan || $this->path_extension != 'php' || !$this->error->empty()) {
            return null;
        }

        if (!empty($this->settings->get('remove_old'))) {
            $content = $this->deleteOldBloc($content);
        }
        if (empty($this->settings->get('bloc_action'))) {
            return null;
        }
        $clean = $this->deleteDocBloc($content);
        if ($this->settings->get('bloc_action') == 'remove') {
            $content = $clean;

            return null;
        }
        if ($content != $clean && $this->settings->get('bloc_action') == 'create') {
            return null;
        }
        if ($content == $clean && $this->settings->get('bloc_action') == 'replace') {
            return null;
        }

        $content = $this->writeDocBloc($clean);

        return true;
    }

    /**
     * Write bloc content in file content.
     *
     * @param   string  $content    Old content
     *
     * @return  string  New content
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
            $this->success->add(__('Write new doc bloc content'));
        }

        return (string) $res;
    }

    /**
     * Delete bloc content in file content.
     *
     * @param   string  $content    Old content
     *
     * @return  string  New content
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
            $this->success->add(__('Delete old doc bloc content'));
        }

        return (string) $res;
    }

    /**
     * Delete old style bloc content in file content.
     *
     * @param   string  $content    Old content
     *
     * @return  string  New content
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
            $this->success->add(__('Delete old style bloc content'));
        }

        return (string) $res;
    }
}
