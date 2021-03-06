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

namespace plugins\improve\module;

/* improve */
use plugins\improve\action;

/* clearbricks */
use form;

/**
 * Improve action module Github shields.io
 */
class gitshields extends action
{
    /** @var string Username of git repo */
    private $username = '';

    /** @var boolean add Dotaddict shield */
    private $dotaddict = false;

    /** @var boolean Stop scaning files */
    private $stop_scan = false;

    /** @var array Parsed bloc */
    private $blocs = [];

    /** @var array Search patterns */
    protected $bloc_pattern = [
        'remove' => '/\[!\[Release(.*)LICENSE\)/ms',
        'target' => '/^([^\n]+)[\r\n|\n]{1,}/ms',
    ];

    /** @var array Shields patterns */
    protected $bloc_content = [
        'release'   => '[![Release](https://img.shields.io/github/v/release/%username%/%module%)](https://github.com/%username%/%module%/releases)',
        'date'      => '[![Date](https://img.shields.io/github/release-date/%username%/%module%)](https://github.com/%username%/%module%/releases)',
        'issues'    => '[![Issues](https://img.shields.io/github/issues/%username%/%module%)](https://github.com/%username%/%module%/issues)',
        'dotclear'  => '[![Dotclear](https://img.shields.io/badge/dotclear-v%dotclear%-blue.svg)](https://fr.dotclear.org/download)',
        'dotaddict' => '[![Dotaddict](https://img.shields.io/badge/dotaddict-official-green.svg)](https://%type%s.dotaddict.org/dc2/details/%module%)',
        'license'   => '[![License](https://img.shields.io/github/license/%username%/%module%)](https://github.com/%username%/%module%/blob/master/LICENSE)',
    ];

    protected function init(): bool
    {
        $this->setProperties([
            'id'           => 'gitshields',
            'name'         => __('Shields badges'),
            'description'  => __('Add and maintain shields.io badges to the REDAME.md file'),
            'priority'     => 380,
            'configurator' => true,
            'types'        => ['plugin', 'theme'],
        ]);

        $username        = $this->getSetting('username');
        $this->username  = is_string($username) ? $username : '';
        $this->dotaddict = (bool) $this->getSetting('dotaddict');

        return true;
    }

    public function isConfigured(): bool
    {
        return !empty($this->getSetting('username'));
    }

    public function configure($url): ?string
    {
        if (!empty($_POST['save']) && !empty($_POST['username'])) {
            $this->setSettings([
                'username'  => (string) $_POST['username'],
                'dotaddict' => !empty($_POST['dotaddict']),
            ]);
            $this->redirect($url);
        }

        return '
        <p><label for="username">' . __('Your Github user name :') . '</label>' .
        form::field('username', 60, 100, $this->username) . '
        </p><p class="form-note">' . __('Used in your Github URL: http://github.com/username/module_id.') . '<br />' .
        __('If you have badges not created by this tool in the README.md file you should remove them manually.') . '</p>

        <p><label for="dotaddict">' .
        form::checkbox('dotaddict', 1, $this->dotaddict) . ' ' .
        __('Include Dotaddict badge') . '</label>
        </p><p class="form-note">' . __('If your plugin or theme is on Dotaddict, you can add a badge to link to its details in Dotaddict.') . '</p>';
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

    private function replaceInfo(): void
    {
        $blocs = [];
        foreach ($this->bloc_content as $k => $v) {
            if ($k == 'dotaddict' && !$this->dotaddict) {
                continue;
            }
            $blocs[$k] = trim(str_replace(
                [
                    '%username%',
                    '%module%',
                    '%dotclear%',
                    '%type%',
                    "\r\n", "\n",
                ],
                [
                    $this->username,
                    $this->module['id'],
                    $dotclear = $this->getDotclearVersion(),
                    $this->module['type'],
                    '', '',
                ],
                $v
            ));
        }
        $this->blocs = $blocs;
        $this->setSuccess(__('Prepare custom shield info'));
    }

    private function getDotclearVersion(): string
    {
        $version = null;
        if (!empty($this->module['requires']) && is_array($this->module['requires'])) {
            foreach ($this->module['requires'] as $req) {
                if (!is_array($req)) {
                    $req = [$req];
                }
                if ($req[0] == 'core') {
                    $version = $req[1];

                    break;
                }
            }
        } elseif (!empty($this->module['dc_min'])) {
            $version = $this->module['dc_min'];
        }

        return $version ?: $this->core->getVersion('core');
    }

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
            $this->setSuccess(__('Write new shield bloc'));
        }

        return (string) $res;
    }

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
            $this->setSuccess(__('Delete old shield bloc'));
        }

        return (string) $res;
    }
}
