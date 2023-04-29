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

namespace Dotclear\Plugin\improve\Task;

use Dotclear\Helper\File\Files;
use Dotclear\Helper\Html\Form\{
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
    Core,
    TaskDescriptor
};

/**
 * Improve action module new line
 */
class NewLine extends Task
{
    protected function getProperties(): TaskDescriptor
    {
        return new TaskDescriptor(
            id: 'newline',
            name: __('Newlines'),
            description: __('Replace bad and repetitive and empty newline by single newline in files'),
            configurator: true,
            types: ['plugin', 'theme'],
            priority: 840
        );
    }

    protected function init(): bool
    {
        return true;
    }

    public function isConfigured(): bool
    {
        return !empty($this->settings->get('extensions'));
    }

    public function configure(string $url): string
    {
        if (!empty($_POST['save']) && !empty($_POST['newline_extensions'])) {
            $this->settings->set(
                'extensions',
                self::cleanExtensions($_POST['newline_extensions'])
            );
            $this->redirect($url);
        }

        $ext = $this->settings->get('extensions');
        if (!is_array($ext)) {
            $ext = [];
        }

        return (new Div())->items([
            (new Fieldset())->class('fieldset')->legend((new Legend(__('Contents'))))->fields([
                // newline_extensions
                (new Para())->items([
                    (new Label(__('List of files extension to work on:'), Label::OUTSIDE_LABEL_BEFORE))->for('newline_extensions'),
                    (new Input('newline_extensions'))->size(65)->maxlenght(255)->value(implode(',', $ext)),
                ]),
                (new Note())->text(__('Use comma separated list of extensions without dot, recommand "php,js,xml,txt,md".'))->class('form-note'),
            ]),
        ])->render();
    }

    public function readFile(string &$content): ?bool
    {
        $ext = $this->settings->get('extensions');
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
            $this->success->add(__('Replace bad new lines'));
            $content = $clean;
        }

        return true;
    }

    /**
     * Check and clean file extension
     *
     * @param  string|array  $in    Extension(s) to clean
     * @return array                Cleaned extension(s)
     */
    private static function cleanExtensions($in): array
    {
        $out = [];
        if (!is_array($in)) {
            $in = explode(',', $in);
        }
        if (!empty($in)) {
            foreach ($in as $v) {
                $v = trim(Files::getExtension('a.' . $v));
                if (!empty($v)) {
                    $out[] = $v;
                }
            }
        }

        return $out;
    }
}
