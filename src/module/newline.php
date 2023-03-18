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

/* dotclear */
use Dotclear\Helper\Html\Form\{
    Div,
    Fieldset,
    Input,
    Label,
    Legend,
    Note,
    Para
};

/* improve */
use Dotclear\Plugin\improve\Action;
use Dotclear\Plugin\improve\Core;

/* clearbricks */

/**
 * Improve action module new line
 */
class newline extends Action
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
                Core::cleanExtensions($_POST['newline_extensions'])
            );
            $this->redirect($url);
        }

        $ext = $this->getSetting('extensions');
        if (!is_array($ext)) {
            $ext = [];
        }

        return (new Div())->items([
            (new Fieldset())->class('fieldset')->legend((new Legend(__('Contents'))))->fields([
                // newline_extensions
                (new Para())->items([
                    (new Label(__('List of files extension to work on:')))->for('newline_extensions'),
                    (new Input('newline_extensions'))->size(65)->maxlenght(255)->value(implode(',', $ext)),
                ]),
                (new Note())->text(__('Use comma separated list of extensions without dot, recommand "php,js,xml,txt,md".'))->class('form-note'),
            ]),
        ])->render();
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
