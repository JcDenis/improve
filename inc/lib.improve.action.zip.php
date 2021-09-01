<?php
/**
 * @brief improve, a plugin for Dotclear 2
 * 
 * @package Dotclear
 * @subpackage plugin
 * 
 * @author Jean-Christian Denis and contributors
 * 
 * @copyright Jean-Christian Denis
 * @copyright GPL-2.0 https://www.gnu.org/licenses/gpl-2.0.html
 */

class ImproveActionZip extends ImproveAction
{
    public static $exclude = [
        '.',
        '..',
        '__MACOSX',
        '.svn',
        '.hg*',
        '.git*',
        'CVS',
        '.DS_Store',
        'Thumbs.db'
    ];
    public static $filename_wildcards = [
        '%type%',
        '%id%',
        '%version%',
        '%author%',
        '%time%'
    ];

    private $package = '';

    protected function init(): bool
    {
        $this->setProperties([
            'id' => 'zip',
            'name' => __('Zip module'),
            'desc' => __('Compress module into a ready to install package'),
            'priority' => 980,
            'config' => true,
            'types' => ['plugin', 'theme']
        ]);

        return true;
    }

    public function isConfigured(): bool
    {
        return !empty($this->getPreference('pack_repository')) && !empty($this->getPreference('pack_filename'));
    }

    public function configure($url): ?string
    {
        if (!empty($_POST['save'])) {
            $this->setPreferences([
                'pack_repository'     => !empty($_POST['pack_repository']) ? $_POST['pack_repository'] : '',
                'pack_filename'       => !empty($_POST['pack_filename']) ? $_POST['pack_filename'] : '',
                'secondpack_filename' => !empty($_POST['secondpack_filename']) ? $_POST['secondpack_filename'] : '',
                'pack_overwrite'      => !empty($_POST['pack_overwrite']),
                'pack_excludefiles'   => !empty($_POST['pack_excludefiles']) ? $_POST['pack_excludefiles'] : '',
                'pack_nocomment'      => !empty($_POST['pack_nocomment'])
            ]);
            $this->redirect($url);
        }

        return '
        <div class="fieldset">
        <h4>' . __('Root') . '</h4>

        <p><label for="pack_repository">' . __('Path to repository:') . ' ' .
        form::field('pack_repository', 65, 255, $this->getPreference('pack_repository'), 'maximal') .
        '</label></p>' .
        '<p class="form-note">' . sprintf(__('Preconization: %s'), $this->core->blog->public_path ?
            path::real($this->core->blog->public_path) : __("Blog's public directory")
        ) . '</p>
        </div>

        <div class="fieldset">
        <h4>' . __('Files') . '</h4>

        <p><label for="pack_filename">' . __('Name of exported package:') . ' ' .
        form::field('pack_filename', 65, 255, $this->getPreference('pack_filename'), 'maximal') .
        '</label></p>
        <p class="form-note">' . sprintf(__('Preconization: %s'), '%type%-%id%-%version%') . '</p>

        <p><label for="secondpack_filename">' . __('Name of second exported package:') . ' ' .
        form::field('secondpack_filename', 65, 255, $this->getPreference('secondpack_filename'), 'maximal') .
        '</label></p>
        <p class="form-note">' . sprintf(__('Preconization: %s'), '%type%-%id%') . '</p>

        <p><label class="classic" for="pack_overwrite">'.
        form::checkbox('pack_overwrite', 1, !empty($this->getPreference('pack_overwrite'))) . ' ' .
        __('Overwrite existing package') . '</label></p>

        </div>

        <div class="fieldset">
        <h4>' . __('Content') . '</h4>

        <p><label for="pack_excludefiles">' . __('Extra files to exclude from package:') . ' ' .
        form::field('pack_excludefiles', 65, 255, $this->getPreference('pack_excludefiles'), 'maximal') .
        '</label></p>
        <p class="form-note">' . sprintf(__('Preconization: %s'), '*.zip,*.tar,*.tar.gz') . '</p>

        <p><label class="classic" for="pack_nocomment">' .
        form::checkbox('pack_nocomment', 1, $this->getPreference('pack_nocomment')) . ' ' .
        __('Remove comments from files') . '</label></p>

        </div>';
    }

    public function closeModule(string $module_type, array $module_info): ?bool
    {
        $exclude = array_merge(
            self::$exclude, 
            explode(',', $this->getPreference('pack_excludefiles'))
        );
        if (!empty($this->getPreference('pack_nocomment'))) {
            ImproveZipFileZip::$remove_comment = true;
        }
        if (!empty($this->getPreference('pack_filename'))) {
            $this->zipModule($this->getPreference('pack_filename'), $exclude);
        }
        if (!empty($this->getPreference('secondpack_filename'))) {
            $this->zipModule($this->getPreference('secondpack_filename'), $exclude);
        }
        return null;
    }

    private function zipModule(string $file, array $exclude)
    {
        $file = str_replace(
            self::$filename_wildcards,
            [
                $this->module['type'],
                $this->module['id'],
                $this->module['version'],
                $this->module['author'],
                time()
            ],
            $file
        );
        $parts = explode('/', $file);
        foreach($parts as $i => $part) {
            $parts[$i] = files::tidyFileName($part);
        }
        $path = $this->getPreference('pack_repository') . '/' . implode('/', $parts) . '.zip';
        if (file_exists($path) && empty($this->getPreference('pack_overwrite'))) {
            self::notice(__('Destination filename already exists'), false);

            return null;
        }
        if (!is_dir(dirname($path)) || !is_writable(dirname($path))) {
            self::notice(__('Destination path is not writable'));
        }
        @set_time_limit(300);
        $fp = fopen($path, 'wb');
        $zip = new ImproveZipFileZip($fp);
        foreach($exclude as $e) {
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
        $zip->write();
        $zip->close();
        unset($zip);

        return null;
    }
}

class ImproveZipFileZip extends fileZip
{
    public static $remove_comment = false;

    protected function writeFile($name, $file, $size, $mtime)
    {
        if (!isset($this->entries[$name])) {
            return null;
        }

        $size = filesize($file);
        $this->memoryAllocate($size * 3);

        $content = file_get_contents($file);

        if (self::$remove_comment && substr($file, -4) == '.php') {
            $content = self::removePHPComment($content);
        }

        $unc_len = strlen($content);
        $crc = crc32($content);
        $zdata = gzdeflate($content);
        $c_len = strlen($zdata);

        unset($content);

        $mdate = $this->makeDate($mtime);
        $mtime = $this->makeTime($mtime);

        # Data descriptor
        $data_desc =
        "\x50\x4b\x03\x04" .
        "\x14\x00" .               # ver needed to extract
        "\x00\x00" .               # gen purpose bit flag
        "\x08\x00" .               # compression method
        pack('v', $mtime) .        # last mod time
        pack('v', $mdate) .        # last mod date
        pack('V', $crc) .          # crc32
        pack('V', $c_len) .        # compressed filesize
        pack('V', $unc_len) .      # uncompressed filesize
        pack('v', strlen($name)) . # length of filename
        pack('v', 0) .             # extra field length
        $name .                    # end of "local file header" segment
        $zdata .                   # "file data" segment
        pack('V', $crc) .          # crc32
        pack('V', $c_len) .        # compressed filesize
        pack('V', $unc_len);       # uncompressed filesize

        fwrite($this->fp, $data_desc);
        unset($zdata);

        $new_offset = $this->old_offset + strlen($data_desc);

        # Add to central directory record
        $cdrec =
        "\x50\x4b\x01\x02" .
        "\x00\x00" .                  # version made by
        "\x14\x00" .                  # version needed to extract
        "\x00\x00" .                  # gen purpose bit flag
        "\x08\x00" .                  # compression method
        pack('v', $mtime) .           # last mod time
        pack('v', $mdate) .           # last mod date
        pack('V', $crc) .             # crc32
        pack('V', $c_len) .           # compressed filesize
        pack('V', $unc_len) .         # uncompressed filesize
        pack('v', strlen($name)) .    # length of filename
        pack('v', 0) .                # extra field length
        pack('v', 0) .                # file comment length
        pack('v', 0) .                # disk number start
        pack('v', 0) .                # internal file attributes
        pack('V', 32) .               # external file attributes - 'archive' bit set
        pack('V', $this->old_offset). # relative offset of local header
        $name;

        $this->old_offset = $new_offset;
        $this->ctrl_dir[] = $cdrec;
    }

    protected static function removePHPComment($content)
    {
        $comment = [T_COMMENT];
        if (defined('T_DOC_COMMENT')) {
            $comment[] = T_DOC_COMMENT; // PHP 5
        }
        if (defined('T_ML_COMMENT')) {
            $comment[] = T_ML_COMMENT; // PHP 4
        }

        $newStr = '';
        $tokens = token_get_all($content);

        foreach ($tokens as $token) {
            if (is_array($token)) {
                if (in_array($token[0], $comment)) {
                    //$newStr .= "\n";
                } else {
                    $newStr .= $token[1];
                }
            } else {
                $newStr .= $token;
            }
        }
        return $newStr;
    }
}