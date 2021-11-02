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
class ImproveActionPhpcsfixer extends ImproveAction
{
    protected static $errors = [
        0  => 'OK.',
        1  => 'General error (or PHP minimal requirement not matched).',
        4  => 'Some files have invalid syntax (only in dry-run mode).',
        8  => 'Some files need fixing (only in dry-run mode).',
        16 => 'Configuration error of the application.',
        32 => 'Configuration error of a Fixer.',
        64 => 'Exception raised within the application'
    ];

    protected function init(): bool
    {
        $this->setProperties([
            'id'       => 'phpcsfixer',
            'name'     => __('PHP CS Fixer'),
            'desc'     => __('Fix PSR coding style using Php CS Fixer'),
            'priority' => 920,
            'config'   => true,
            'types'    => ['plugin', 'theme']
        ]);

        return true;
    }

    public function isConfigured(): bool
    {
        return !empty($this->getSetting('phpcsf_path'));
    }

    public function configure($url): ?string
    {
        if (!empty($_POST['save'])) {
            $this->setSettings([
                'phpexe_path' => !empty($_POST['phpexe_path']) ? $_POST['phpexe_path'] : '',
                'phpcsf_path' => !empty($_POST['phpcsf_path']) ? $_POST['phpcsf_path'] : ''
            ]);
            $this->redirect($url);
        }

        return
        '<p class="info">' . sprintf(
            __('You must have installed %s to use this tool'),
            '<a href="https://github.com/FriendsOfPHP/PHP-CS-Fixer">php-cs-fixer</a>'
        ) . '</p>' .
        '<p><label class="classic" for="phpexe_path">' .
        __('Root directory of PHP executable:') . '<br />' .
        form::field('phpexe_path', 160, 255, $this->getSetting('phpexe_path')) . '</label>' .
        '</p>' .
        '<p class="form-note">' .
            __('If this server is under unix, leave it empty.') . ' ' .
            __('If this server is under Windows, put here directory to php executable (without executable file name).') .
        ' C:\path_to\php</p>' .
        '<p><label class="classic" for="phpcsf_path">' .
        __('Root directory to "friendsofphp php-cs-fixer":') . '<br />' .
        form::field('phpcsf_path', 160, 255, $this->getSetting('phpcsf_path')) . '</label>' .
        '</p>' .
        '<p class="form-note">' . __('Do not add file name to the end of path.') . ' \path_to\tools\php-cs-fixer\vendor\friendsofphp\php-cs-fixer</p>';
    }

    public function closeModule(): ?bool
    {
        $phpexe_path = path::real($this->getSetting('phpexe_path'));
        if (!empty($phpexe_path)) {
            $phpexe_path .= '/';
        }
        $phpcsf_path = path::real($this->getSetting('phpcsf_path'));

        $command = sprintf(
            '%sphp %s/php-cs-fixer fix %s --config=%s/dc.phpcsfixer.rules.php --using-cache=no',
            $phpexe_path,
            $phpcsf_path,
            $this->module['sroot'],
            dirname(__FILE__)
        );

        try {
            exec($command, $output, $error);
            if (empty($output)) {
                if (isset(self::$errors[$error])) {
                    $this->setError(self::$errors[$error]);

                    return false;
                }

                throw new Exception('oops');
            }
            $this->setSuccess(sprintf('<pre>%s</pre>', implode('<br />', $output)));

            return true;
        } catch (Exception $e) {
            $this->setError(__('Failed to run php-cs-fixer'));

            return false;
        }
    }
}
