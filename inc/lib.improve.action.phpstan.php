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
class ImproveActionPhpstan extends ImproveAction
{
    protected function init(): bool
    {
        $this->setProperties([
            'id'       => 'phpstan',
            'name'     => __('PHPStan'),
            'desc'     => __('Analyse php code using PHPStan'),
            'priority' => 910,
            'config'   => true,
            'types'    => ['plugin']
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
            $this->setSettings([
                'phpexe_path' => (!empty($_POST['phpexe_path']) ? $_POST['phpexe_path'] : ''),
                'run_level' => (integer) $_POST['run_level']
            ]);
            $this->redirect($url);
        }

        return
        '<p><label class="classic" for="phpexe_path">' .
        __('Root directory of PHP executable:') . '<br />' .
        form::field('phpexe_path', 160, 255, $this->getSetting('phpexe_path')) . '</label>' .
        '</p>' .
        '<p class="form-note">' .
            __('If this module does not work you can try to put here directory to php executable (without executable file name).') .
        ' C:\path_to\php</p>' .
        '<p><label class="classic" for="run_level">' . __('Level:') . ' </label>' .
        form::number('run_level', ['min' => 0, 'max' => 9, 'default' => (integer) $this->getSetting('run_level')]) . '</p>' .
        '<p class="info">' . __('You must enable improve details to view analyse results !') . '</p>';
    }

    public function closeModule(): ?bool
    {
        $phpexe_path = $this->getPhpPath();
        if (!empty($phpexe_path)) {
            $phpexe_path .= '/';
        }

        if (!$this->writeConf()) {
            $this->setError(__('Failed to write phpstan configuration'));

            return false;
        }

        $command = sprintf(
            '%sphp %s/libs/phpstan.phar analyse --configuration=%s',
            $phpexe_path,
            dirname(__FILE__),
            DC_VAR . '/phpstan.neon'
        );

        try {
            exec($command, $output, $error);

            if (!empty($error) && empty($output)) {
                throw new Exception('oops');
            }
            if (empty($output)) {
                $output[] = __('No errors found');
            }
            $this->setSuccess(sprintf('<pre>%s</pre>', implode('<br />', $output)));

            return true;
        } catch (Exception $e) {
            $this->setError(__('Failed to run phpstan'));

            return false;
        }
    }

    private function getPhpPath()
    {
        $phpexe_path = $this->getSetting('phpexe_path');
        if (empty($phpexe_path) && !empty(PHP_BINDIR)) {
            $phpexe_path = PHP_BINDIR;
        }

        return path::real($phpexe_path);
    }

    private function writeConf()
    {
        $content = 
            "parameters:\n" .
            "  level: " . (integer) $this->getSetting('run_level') . "\n\n" .
            "  paths: \n" .
            "    - " . $this->module['sroot'] . "\n\n" .
            "  scanFiles:\n" .
            "    - " . DC_ROOT . "/index.php\n" .
            "  scanDirectories:\n" .
            "    - " . DC_ROOT . "\n" .
            "  excludePaths:\n" .
            "    - " . $this->module['sroot'] . "/*/libs/*\n\n" .
            "  bootstrapFiles:\n" .
            "    - " . dirname(__FILE__) . "/libs/dc.phpstan.bootstrap.php\n\n" .
            "  fileExtensions:\n" .
            "    - php\n" .
            "    - in\n\n" .
            // extra
            "  checkMissingIterableValueType: false\n" .
            "  checkGenericClassInNonGenericObjectType: false\n";

        return file_put_contents(DC_VAR . '/phpstan.neon', $content);

    }
}
