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
class ImproveActionTab extends ImproveAction
{
    protected function init(): bool
    {
        $this->setProperties([
            'id'       => 'tab',
            'name'     => __('Tabulations'),
            'desc'     => __('Replace tabulation by four space in php files'),
            'priority' => 820,
            'types'    => ['plugin', 'theme']
        ]);

        return true;
    }

    public function readFile(&$content): ?bool
    {
        if (!in_array($this->path_extension, ['php', 'md'])) {
            return null;
        }
        $clean = preg_replace('/(\t)/', '    ', $content);// . "\n";
        if ($content != $clean) {
            $this->setSuccess(__('Replace tabulation by spaces'));
            $content = $clean;
        }

        return true;
    }

    public function isConfigured(): bool
    {
        return true;
    }
}

class ImproveActionNewline extends ImproveAction
{
    private $extensions = ['php', 'js', 'xml', 'md', 'txt'];

    protected function init(): bool
    {
        $this->setProperties([
            'id'       => 'newline',
            'name'     => __('Newlines'),
            'desc'     => __('Replace bad and repetitive and empty newline by single newline in files'),
            'priority' => 840,
            'config'   => true,
            'types'    => ['plugin', 'theme']
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
                Improve::cleanExtensions($_POST['newline_extensions'])
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

    public function readFile(&$content): ?bool
    {
        $ext = $this->getSetting('extensions');
        if (!is_array($ext) || !in_array($this->path_extension, $ext)) {
            return null;
        }
        $clean = preg_replace(
            '/(\n\s+\n)/',
            "\n\n",
            preg_replace(
                '/(\n\n+)/',
                "\n\n",
                str_replace(
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

class ImproveActionEndoffile extends ImproveAction
{
    private $psr2 = false;

    protected function init(): bool
    {
        $this->setProperties([
            'id'       => 'endoffile',
            'name'     => __('End of files'),
            'desc'     => __('Remove php tag and empty lines from end of files'),
            'priority' => 860,
            'config'   => true,
            'types'    => ['plugin', 'theme']
        ]);

        return true;
    }

    public function isConfigured(): bool
    {
        return true;
    }

    public function configure($url): ?string
    {
        if (!empty($_POST['save'])) {
            $this->setSettings('psr2', !empty($_POST['endoffile_psr2']));
            $this->redirect($url);
        }

        return
        '<p><label class="classic" for="endoffile_psr2">' .
        form::checkbox('endoffile_psr2', 255, $this->getSetting('psr2')) .
        __('Add a blank line to the end of file') .
        '</label></p><p class="form-note">' .
        __('PSR2 must have a blank line, whereas PSR12 must not.') .
        '</p>';
    }

    public function readFile(&$content): ?bool
    {
        if (!in_array($this->path_extension, ['php', 'md'])) {
            return null;
        }
        $clean = preg_replace(
            ['/(\s*)(\?>\s*)$/', '/\n+$/'],
            '',
            $content
        ) . ($this->getSetting('psr2') ? "\n" : '');
        if ($content != $clean) {
            $this->setSuccess(__('Replace end of file'));
            $content = $clean;
        }

        return true;
    }
}
