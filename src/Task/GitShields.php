<?php

declare(strict_types=1);

namespace Dotclear\Plugin\improve\Task;

use Dotclear\App;
use Dotclear\Helper\Html\Form\{
    Checkbox,
    Div,
    Fieldset,
    Input,
    Label,
    Legend,
    Note,
    Para
};
use Dotclear\Plugin\improve\{
    Task,
    TaskDescriptor
};

/**
 * @brief       improve task: EOF class.
 * @ingroup     improve
 *
 * @author      Jean-Christian Denis
 * @copyright   GPL-2.0 https://www.gnu.org/licenses/gpl-2.0.html
 */
class GitShields extends Task
{
    /**
     * Username of git repo.
     * @var     string  $username
     */
    private $username = '';

    /**
     * Domain of git repo.
     *
     * @var     string  $domain
     */
    private $domain = 'github.com';

    /**
     * Add Dotaddict shield.
     *
     * @var     bool    $dotaddict
     */
    private $dotaddict = false;

    /**
     * Stop scaning files.
     *
     * @var bool    $stop_scan
     */
    private $stop_scan = false;

    /**
     * Parsed bloc.
     *
     * @var     array<string, string>   $blocs
     */
    private $blocs = [];

    /**
     * Search patterns.
     *
     * @var     array<string, string>   $bloc_pattern
     */
    protected $bloc_pattern = [
        'remove' => '/\[!\[Release(.*)LICENSE\)/ms',
        'target' => '/^([^\n]+)[\r\n|\n]{1,}/ms',
    ];

    /**
     * Shields patterns.
     *
     * @var     array<string, string>   $bloc_content
     */
    protected $bloc_content = [
        'release' => '[![Release](https://img.shields.io/badge/release-%version%-a2cbe9.svg)](https://%domain%/%username%/%module%/releases)',
        'date'    => '![Date](https://img.shields.io/badge/date-%date%-c44d58.svg)',
        #        'issues'    => '[![Issues](https://img.shields.io/github/issues/%username%/%module%)](https://%domain%/%username%/%module%/issues)',
        'dotclear'  => '[![Dotclear](https://img.shields.io/badge/dotclear-v%dotclear%-137bbb.svg)](https://fr.dotclear.org/download)',
        'dotaddict' => '[![Dotaddict](https://img.shields.io/badge/dotaddict-official-9ac123.svg)](https://%type%s.dotaddict.org/dc2/details/%module%)',
        'license'   => '[![License](https://img.shields.io/github/license/%username%/%module%)](https://%domain%/%username%/%module%/blob/master/LICENSE)',
    ];

    protected function getProperties(): TaskDescriptor
    {
        return new TaskDescriptor(
            id: 'gitshields',
            name: __('Shields badges'),
            description: __('Add and maintain shields.io badges to the REDAME.md file'),
            configurator: true,
            types: ['plugin', 'theme'],
            priority: 380
        );
    }

    protected function init(): bool
    {
        $username        = $this->settings->get('username');
        $this->username  = is_string($username) ? $username : '';
        $domain          = $this->settings->get('domain');
        $this->domain    = is_string($domain) ? $domain : 'github.com';
        $this->dotaddict = (bool) $this->settings->get('dotaddict');

        return true;
    }

    public function isConfigured(): bool
    {
        return !empty($this->settings->get('username'));
    }

    public function configure(string $url): string
    {
        if (!empty($_POST['save']) && !empty($_POST['username'])) {
            $this->settings->set([
                'username'  => (string) $_POST['username'],
                'domain'    => (string) $_POST['domain'],
                'dotaddict' => !empty($_POST['dotaddict']),
            ]);
            $this->redirect($url);
        }

        return (new Div())->items([
            (new Fieldset())->class('fieldset')->legend((new Legend(__('Contents'))))->fields([
                // domain
                (new Para())->items([
                    (new Label(__('Your Github repository domain:'), Label::OUTSIDE_LABEL_BEFORE))->for('domain'),
                    (new Input('domain'))->size(65)->maxlenght(255)->value($this->domain),
                ]),
                (new Note())->text(__('By default this is github.com or you can specify a custom gitea domain'))->class('form-note'),
                // username
                (new Para())->items([
                    (new Label(__('Your Github user name:'), Label::OUTSIDE_LABEL_BEFORE))->for('username'),
                    (new Input('username'))->size(65)->maxlenght(255)->value($this->username),
                ]),
                (new Note())->text(__('Used in your Github URL: http://github.com/username/module_id.'))->class('form-note'),
                (new Note())->text(__('If you have badges not created by this tool in the README.md file you should remove them manually.'))->class('form-note'),
                // dotaddict
                (new Para())->items([
                    (new Checkbox('dotaddict', $this->dotaddict))->value(1),
                    (new Label(__('Include Dotaddict badge'), Label::OUTSIDE_LABEL_AFTER))->for('dotaddict')->class('classic'),
                ]),
                (new Note())->text(__('If your plugin or theme is on Dotaddict, you can add a badge to link to its details in Dotaddict.'))->class('form-note'),
            ]),
        ])->render();
    }

    public function openModule(): ?bool
    {
        $this->replaceInfo();

        return null;
    }

    public function readFile(&$content): ?bool
    {
        if ($this->stop_scan || !preg_match('/(.*?)README\.md$/i', $this->path_full)) {
            return null;
        }

        $clean           = $this->deleteShieldsBloc($content);
        $content         = $this->writeShieldsBloc($clean);
        $this->stop_scan = true;

        return true;
    }

    /**
     * Parse module and user info.
     */
    private function replaceInfo(): void
    {
        $blocs = [];
        foreach ($this->bloc_content as $k => $v) {
            if ($k == 'dotaddict' && !$this->dotaddict) {
                continue;
            }
            $blocs[$k] = trim(str_replace(
                [
                    '%domain%',
                    '%username%',
                    '%module%',
                    '%version%',
                    '%date%',
                    '%dotclear%',
                    '%type%',
                    "\r\n", "\n",
                ],
                [
                    $this->domain,
                    $this->username,
                    $this->module->getId(),
                    str_replace(['-', '_'], ['--', '__'], $this->module->get('version')),
                    date('Y.m.d', time()),
                    $dotclear = $this->getDotclearVersion(),
                    $this->module->get('type'),
                    '', '',
                ],
                $v
            ));
        }
        $this->blocs = $blocs;
        $this->success->add(__('Prepare custom shield info'));
    }

    /**
     * Get dotclear version.
     */
    private function getDotclearVersion(): string
    {
        $version = null;
        if (!empty($this->module->get('requires')) && is_array($this->module->get('requires'))) {
            foreach ($this->module->get('requires') as $req) {
                if (!is_array($req)) {
                    $req = [$req];
                }
                if ($req[0] == 'core') {
                    $version = $req[1];

                    break;
                }
            }
        } elseif (!empty($this->module->get('dc_min'))) {
            $version = $this->module->get('dc_min');
        }

        return $version ?: App::version()->getVersion('core');
    }

    /**
     * Write shield bloc.
     *
     * @param   string  $content    The file content
     *
     * @return  string  The README file content
     */
    private function writeShieldsBloc(string $content): string
    {
        $res = preg_replace(
            $this->bloc_pattern['target'],
            '$1' . "\n\n" . trim(implode("\n", $this->blocs)) . "\n\n",
            $content,
            1,
            $count
        );
        if ($count && $res) {
            $this->success->add(__('Write new shield bloc'));
        }

        return (string) $res;
    }

    /**
     * Delete existing shield bloc.
     *
     * @param   string  $content    The file content
     *
     * @return  string  The README file content
     */
    private function deleteShieldsBloc(string $content): string
    {
        $res = preg_replace(
            $this->bloc_pattern['remove'],
            "\n\n",
            $content,
            1,
            $count
        );
        if ($count && $res) {
            $this->success->add(__('Delete old shield bloc'));
        }

        return (string) $res;
    }
}
