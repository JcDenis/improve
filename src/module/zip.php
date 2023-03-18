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
use dcCore;

/* improve */
use Dotclear\Plugin\improve\Action;

/* clearbricks */
use form;
use path;
use files;

/**
 * Improve action module zip
 */
class zip extends action
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

        return '
        <div class="fieldset">
        <h4>' . __('Root') . '</h4>

        <p><label for="pack_repository">' . __('Path to repository:') . ' ' .
        form::field('pack_repository', 65, 255, $this->getSetting('pack_repository'), 'maximal') .
        '</label></p>' .
        '<p class="form-note">' . sprintf(
            __('Preconization: %s'),
            dcCore::app()->blog->public_path ?
            path::real(dcCore::app()->blog->public_path) : __("Blog's public directory")
        ) . '</p>
        </div>

        <div class="fieldset">
        <h4>' . __('Files') . '</h4>

        <p><label for="pack_filename">' . __('Name of exported package:') . ' ' .
        form::field('pack_filename', 65, 255, $this->pack_filename, 'maximal') .
        '</label></p>
        <p class="form-note">' . sprintf(__('Preconization: %s'), '%type%-%id%') . '</p>

        <p><label for="secondpack_filename">' . __('Name of second exported package:') . ' ' .
        form::field('secondpack_filename', 65, 255, $this->getSetting('secondpack_filename'), 'maximal') .
        '</label></p>
        <p class="form-note">' . sprintf(__('Preconization: %s'), '%type%-%id%-%version%') . '</p>

        <p><label class="classic" for="pack_overwrite">' .
        form::checkbox('pack_overwrite', 1, !empty($this->getSetting('pack_overwrite'))) . ' ' .
        __('Overwrite existing package') . '</label></p>

        </div>

        <div class="fieldset">
        <h4>' . __('Content') . '</h4>

        <p><label for="pack_excludefiles">' . __('Extra files to exclude from package:') . ' ' .
        form::field('pack_excludefiles', 65, 255, $this->pack_excludefiles, 'maximal') .
        '</label></p>
        <p class="form-note">' . sprintf(__('Preconization: %s'), '*.zip,*.tar,*.tar.gz') . '<br />' .
        sprintf(__('By default all these files are always removed from packages : %s'), implode(', ', self::$exclude)) . '</p>

        <p><label class="classic" for="pack_nocomment">' .
        form::checkbox('pack_nocomment', 1, $this->getSetting('pack_nocomment')) . ' ' .
        __('Remove comments from files') . '</label></p>

        </div>';
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
                $this->module['type'],
                $this->module['id'],
                $this->module['version'],
                $this->module['author'],
                time(),
            ],
            $file
        );
        $parts = explode('/', $file);
        foreach ($parts as $i => $part) {
            $parts[$i] = files::tidyFileName($part);
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
        $zip = new zip\Zip($path);
        foreach ($exclude as $e) {
            $e = '#(^|/)(' . str_replace(
                ['.', '*'],
                ['\.', '.*?'],
                trim($e)
            ) . ')(/|$)#';
            $zip->addExclusion($e);
        }
        $zip->addDirectory(
            path::real($this->module['root']),
            $this->module['id'],
            true
        );
        $zip->close();
        unset($zip);

        $this->setSuccess(sprintf(__('Zip module into "%s"'), $path));
    }
}
