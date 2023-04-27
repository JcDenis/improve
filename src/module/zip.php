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
use Dotclear\Helper\File\{
    Files,
    Path
};
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
use Dotclear\Plugin\improve\Action;

/**
 * Improve action module zip
 */
class zip extends Action
{
    /** @var array List of excluded file pattern */
    public static $exclude = [
        '.',
        '..',
        '__MACOSX',
        '.svn',
        '.hg*',
        '.git*',
        'CVS',
        '.DS_Store',
        'Thumbs.db',
        '_disabled',
    ];

    /** @var array Replacement wildcards */
    public static $filename_wildcards = [
        '%type%',
        '%id%',
        '%version%',
        '%author%',
        '%time%',
    ];

    /** @var string Settings Excluded files */
    private $pack_excludefiles = '';

    /** @var string Settings Main packacge filename */
    private $pack_filename = '';

    /** @var string Settings Second package filename */
    private $secondpack_filename = '';

    protected function init(): bool
    {
        require_once implode(DIRECTORY_SEPARATOR, [__DIR__, 'zip', 'Zip.php']);

        $this->setProperties([
            'id'           => 'zip',
            'name'         => __('Zip module'),
            'description'  => __('Compress module into a ready to install package'),
            'priority'     => 980,
            'configurator' => true,
            'types'        => ['plugin', 'theme'],
        ]);

        $pack_excludefiles       = $this->getSetting('pack_excludefiles');
        $this->pack_excludefiles = is_string($pack_excludefiles) ? $pack_excludefiles : '';

        $pack_filename       = $this->getSetting('pack_filename');
        $this->pack_filename = is_string($pack_filename) ? $pack_filename : '';

        $secondpack_filename       = $this->getSetting('secondpack_filename');
        $this->secondpack_filename = is_string($secondpack_filename) ? $secondpack_filename : '';

        return true;
    }

    public function isConfigured(): bool
    {
        return !empty($this->getSetting('pack_repository')) && !empty($this->getSetting('pack_filename'));
    }

    public function configure($url): ?string
    {
        if (!empty($_POST['save'])) {
            $this->setSettings([
                'pack_repository'     => !empty($_POST['pack_repository']) ? $_POST['pack_repository'] : '',
                'pack_filename'       => !empty($_POST['pack_filename']) ? $_POST['pack_filename'] : '',
                'secondpack_filename' => !empty($_POST['secondpack_filename']) ? $_POST['secondpack_filename'] : '',
                'pack_overwrite'      => !empty($_POST['pack_overwrite']),
                'pack_excludefiles'   => !empty($_POST['pack_excludefiles']) ? $_POST['pack_excludefiles'] : '',
                'pack_nocomment'      => !empty($_POST['pack_nocomment']),
            ]);
            $this->redirect($url);
        }

        return (new Div())->items([
            (new Fieldset())->class('fieldset')->legend((new Legend(__('Root'))))->fields([
                // pack_repository
                (new Para())->items([
                    (new Label(__('Path to repository:'), Label::OUTSIDE_LABEL_BEFORE))->for('pack_repository'),
                    (new Input('pack_repository'))->size(65)->maxlenght(255)->value($this->getSetting('pack_repository')),
                ]),
                (new Note())->text(sprintf(
                    __('Preconization: %s'),
                    dcCore::app()->blog?->public_path ?
                    Path::real(dcCore::app()->blog->public_path) : __("Blog's public directory")
                ))->class('form-note'),
            ]),
            (new Fieldset())->class('fieldset')->legend((new Legend(__('Files'))))->fields([
                // pack_filename
                (new Para())->items([
                    (new Label(__('Name of exported package:'), Label::OUTSIDE_LABEL_BEFORE))->for('pack_filename'),
                    (new Input('pack_filename'))->size(65)->maxlenght(255)->value($this->getSetting('pack_filename')),
                ]),
                (new Note())->text(sprintf(__('Preconization: %s'), '%type%-%id%'))->class('form-note'),
                // secondpack_filename
                (new Para())->items([
                    (new Label(__('Name of second exported package:'), Label::OUTSIDE_LABEL_BEFORE))->for('secondpack_filename'),
                    (new Input('secondpack_filename'))->size(65)->maxlenght(255)->value($this->getSetting('secondpack_filename')),
                ]),
                (new Note())->text(sprintf(__('Preconization: %s'), '%type%-%id%-%version%'))->class('form-note'),
                // pack_overwrite
                (new Para())->items([
                    (new Checkbox('pack_overwrite', !empty($this->getSetting('pack_overwrite'))))->value(1),
                    (new Label(__('Overwrite existing languages'), Label::OUTSIDE_LABEL_AFTER))->for('pack_overwrite')->class('classic'),
                ]),
            ]),
            (new Fieldset())->class('fieldset')->legend((new Legend(__('Contents'))))->fields([
                // pack_excludefiles
                (new Para())->items([
                    (new Label(__('Extra files to exclude from package:'), Label::OUTSIDE_LABEL_BEFORE))->for('pack_excludefiles'),
                    (new Input('pack_excludefiles'))->size(65)->maxlenght(255)->value($this->getSetting('pack_excludefiles')),
                ]),
                (new Note())->text(sprintf(__('By default all these files are always removed from packages : %s'), implode(', ', self::$exclude)))->class('form-note'),
                // pack_nocomment
                (new Para())->items([
                    (new Checkbox('pack_nocomment', !empty($this->getSetting('pack_nocomment'))))->value(1),
                    (new Label(__('Remove comments from files'), Label::OUTSIDE_LABEL_AFTER))->for('pack_nocomment')->class('classic'),
                ]),
            ]),
        ])->render();
    }

    public function closeModule(): ?bool
    {
        $exclude = array_merge(
            self::$exclude,
            explode(',', $this->pack_excludefiles)
        );
        $this->setSuccess(sprintf(__('Prepare excluded files "%s"'), implode(', ', $exclude)));
        if (!empty($this->getSetting('pack_nocomment'))) {
            zip\Zip::$remove_comment = true;
            $this->setSuccess(__('Prepare comment removal'));
        }
        if (!empty($this->getSetting('pack_filename'))) {
            $this->zipModule($this->pack_filename, $exclude);
        }
        if (!empty($this->getSetting('secondpack_filename'))) {
            $this->zipModule($this->secondpack_filename, $exclude);
        }

        return null;
    }

    private function zipModule(string $file, array $exclude): void
    {
        $file = str_replace(
            self::$filename_wildcards,
            [
                $this->module->get('type'),
                $this->module->getId(),
                $this->module->get('version'),
                $this->module->get('author'),
                time(),
            ],
            $file
        );
        $parts = explode('/', $file);
        foreach ($parts as $i => $part) {
            $parts[$i] = Files::tidyFileName($part);
        }
        $path = $this->getSetting('pack_repository') . '/' . implode('/', $parts) . '.zip';
        if (file_exists($path) && empty($this->getSetting('pack_overwrite'))) {
            $this->setWarning(__('Destination filename already exists'));

            return;
        }
        if (!is_dir(dirname($path)) || !is_writable(dirname($path))) {
            $this->setError(__('Destination path is not writable'));

            return;
        }
        @set_time_limit(300);
        $fp  = fopen($path, 'wb');
        $zip = new zip\Zip($fp);
        foreach ($exclude as $e) {
            $e = '#(^|/)(' . str_replace(
                ['.', '*'],
                ['\.', '.*?'],
                trim($e)
            ) . ')(/|$)#';
            $zip->addExclusion($e);
        }
        $zip->addDirectory(
            (string) Path::real($this->module->get('root')),
            $this->module->getId(),
            true
        );
        $zip->close();
        $zip->write();
        unset($zip);

        $this->setSuccess(sprintf(__('Zip module into "%s"'), $path));
    }
}
