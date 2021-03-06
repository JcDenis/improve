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
use plugins\improve\improve;

/* clearbricks */
use form;

/**
 * Improve action module new line
 */
class newline extends action
{
    protected function init(): bool
    {
        $this->setProperties([
            'id'           => 'newline',
            'name'         => __('Newlines'),
            'description'  => __('Replace bad and repetitive and empty newline by single newline in files'),
            'priority'     => 840,
            'configurator' => true,
            'types'        => ['plugin', 'theme'],
        ]);
        /*
                $ext = @unserialize($this->core->blog->settings->improve->newline_extensions);
                $ext = Improve::cleanExtensions($ext);
                if (!empty($ext)) {
                    $this->extensions = $ext;
                }
        */
        return true;
    }

    public function isConfigured(): bool
    {
        return !empty($this->getSetting('extensions'));
    }

    public function configure($url): ?string
    {
        if (!empty($_POST['save']) && !empty($_POST['newline_extensions'])) {
            $this->setSettings(
                'extensions',
                improve::cleanExtensions($_POST['newline_extensions'])
            );
            $this->redirect($url);
        }

        $ext = $this->getSetting('extensions');
        if (!is_array($ext)) {
            $ext = [];
        }

        return
        '<p><label class="classic" for="newline_extensions">' .
        __('List of files extension to work on:') . '<br />' .
        form::field('newline_extensions', 65, 255, implode(',', $ext)) .
        '</label></p><p class="form-note">' .
         __('Use comma separated list of extensions without dot, recommand "php,js,xml,txt,md".') .
         '</p>';
    }

    public function readFile(string &$content): ?bool
    {
        $ext = $this->getSetting('extensions');
        if (!is_array($ext) || !in_array($this->path_extension, $ext)) {
            return null;
        }
        $clean = (string) preg_replace(
            '/(\n\s+\n)/',
            "\n\n",
            (string) preg_replace(
                '/(\n\n+)/',
                "\n\n",
                (string) str_replace(
                    ["\r\n", "\r"],
                    "\n",
                    $content
                )
            )
        );
        if ($content != $clean) {
            $this->setSuccess(__('Replace bad new lines'));
            $content = $clean;
        }

        return true;
    }
}
