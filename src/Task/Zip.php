<?php

declare(strict_types=1);

namespace Dotclear\Plugin\improve\Task;

use Dotclear\App;
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
use Dotclear\Plugin\improve\{
    Task,
    TaskDescriptor
};

/**
 * @brief       improve task: zip class.
 * @ingroup     improve
 *
 * @author      Jean-Christian Denis
 * @copyright   GPL-2.0 https://www.gnu.org/licenses/gpl-2.0.html
 */
class Zip extends Task
{
    /**
     * List of excluded file pattern.
     *
     * @var     array<int, string>  $exclude
     */
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

    /**
     * Replacement wildcards.
     *
     * @var     array<int, string>  $filename_wildcards
     */
    public static $filename_wildcards = [
        '%type%',
        '%id%',
        '%version%',
        '%author%',
        '%time%',
    ];

    /**
     * Settings Excluded files.
     *
     * @var     string  $pack_excludefiles
     */
    private $pack_excludefiles = '';

    /**
     * Settings Main packacge filename.
     *
     * @var string  $pack_filename
     */
    private $pack_filename = '';

    /**
     * Settings Second package filename.
     *
     * @var     string  $secondpack_filename
     */
    private $secondpack_filename = '';

    protected function getProperties(): TaskDescriptor
    {
        return new TaskDescriptor(
            id: 'zip',
            name: __('Zip module'),
            description: __('Compress module into a ready to install package'),
            configurator: true,
            types: ['plugin', 'theme'],
            priority: 980
        );
    }

    protected function init(): bool
    {
        require_once implode(DIRECTORY_SEPARATOR, [__DIR__, 'zip', 'Zip.php']);

        $pack_excludefiles       = $this->settings->get('pack_excludefiles');
        $this->pack_excludefiles = is_string($pack_excludefiles) ? $pack_excludefiles : '';

        $pack_filename       = $this->settings->get('pack_filename');
        $this->pack_filename = is_string($pack_filename) ? $pack_filename : '';

        $secondpack_filename       = $this->settings->get('secondpack_filename');
        $this->secondpack_filename = is_string($secondpack_filename) ? $secondpack_filename : '';

        return true;
    }

    public function isConfigured(): bool
    {
        return !empty($this->settings->get('pack_repository')) && !empty($this->settings->get('pack_filename'));
    }

    public function configure(string $url): string
    {
        if (!empty($_POST['save'])) {
            $this->settings->set([
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
                    (new Input('pack_repository'))->size(65)->maxlenght(255)->value($this->settings->get('pack_repository')),
                ]),
                (new Note())->text(sprintf(
                    __('Preconization: %s'),
                    App::blog()->publicPath() ?
                    Path::real(App::blog()->publicPath()) : __("Blog's public directory")
                ))->class('form-note'),
            ]),
            (new Fieldset())->class('fieldset')->legend((new Legend(__('Files'))))->fields([
                // pack_filename
                (new Para())->items([
                    (new Label(__('Name of exported package:'), Label::OUTSIDE_LABEL_BEFORE))->for('pack_filename'),
                    (new Input('pack_filename'))->size(65)->maxlenght(255)->value($this->settings->get('pack_filename')),
                ]),
                (new Note())->text(sprintf(__('Preconization: %s'), '%type%-%id%'))->class('form-note'),
                // secondpack_filename
                (new Para())->items([
                    (new Label(__('Name of second exported package:'), Label::OUTSIDE_LABEL_BEFORE))->for('secondpack_filename'),
                    (new Input('secondpack_filename'))->size(65)->maxlenght(255)->value($this->settings->get('secondpack_filename')),
                ]),
                (new Note())->text(sprintf(__('Preconization: %s'), '%type%-%id%-%version%'))->class('form-note'),
                // pack_overwrite
                (new Para())->items([
                    (new Checkbox('pack_overwrite', !empty($this->settings->get('pack_overwrite'))))->value(1),
                    (new Label(__('Overwrite existing languages'), Label::OUTSIDE_LABEL_AFTER))->for('pack_overwrite')->class('classic'),
                ]),
            ]),
            (new Fieldset())->class('fieldset')->legend((new Legend(__('Contents'))))->fields([
                // pack_excludefiles
                (new Para())->items([
                    (new Label(__('Extra files to exclude from package:'), Label::OUTSIDE_LABEL_BEFORE))->for('pack_excludefiles'),
                    (new Input('pack_excludefiles'))->size(65)->maxlenght(255)->value($this->settings->get('pack_excludefiles')),
                ]),
                (new Note())->text(sprintf(__('By default all these files are always removed from packages : %s'), implode(', ', self::$exclude)))->class('form-note'),
                // pack_nocomment
                (new Para())->items([
                    (new Checkbox('pack_nocomment', !empty($this->settings->get('pack_nocomment'))))->value(1),
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
        $this->success->add(sprintf(__('Prepare excluded files "%s"'), implode(', ', $exclude)));
        if (!empty($this->settings->get('pack_nocomment'))) {
            zip\Zip::$remove_comment = true;
            $this->success->add(__('Prepare comment removal'));
        }
        if (!empty($this->settings->get('pack_filename'))) {
            $this->zipModule($this->pack_filename, $exclude);
        }
        if (!empty($this->settings->get('secondpack_filename'))) {
            $this->zipModule($this->secondpack_filename, $exclude);
        }

        return null;
    }

    /**
     * Zip module.
     *
     * @param   string              $file       Path to zip
     * @param   array<int, string>  $exclude    files to exlude
     */
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
        $path = $this->settings->get('pack_repository') . '/' . implode('/', $parts) . '.zip';
        if (file_exists($path) && empty($this->settings->get('pack_overwrite'))) {
            $this->warning->add(__('Destination filename already exists'));

            return;
        }
        if (!is_dir(dirname($path)) || !is_writable(dirname($path))) {
            $this->error->add(__('Destination path is not writable'));

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
        $zip->write();
        $zip->close();
        unset($zip);

        $this->success->add(sprintf(__('Zip module into "%s"'), $path));
    }
}
