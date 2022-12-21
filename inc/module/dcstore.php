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

/* improve */
use Dotclear\Plugin\improve\Action;

/* clearbricks */
use form;
use files;
use text;
use xmlTag;
use DOMDocument;

/* php */
use Exception;

/**
 * Improve action module dcstore.xml
 */
class dcstore extends Action
{
    /** @var string Settings dcstore zip url pattern */
    private $pattern = '';

    protected function init(): bool
    {
        $this->setProperties([
            'id'           => 'dcstore',
            'name'         => __('Store file'),
            'description'  => __('Re-create dcstore.xml file according to _define.php variables'),
            'priority'     => 420,
            'configurator' => true,
            'types'        => ['plugin', 'theme'],
        ]);

        $pattern       = $this->getSetting('pattern');
        $this->pattern = is_string($pattern) ? $pattern : '';

        return true;
    }

    public function isConfigured(): bool
    {
        return !empty($this->getSetting('pattern'));
    }

    public function configure($url): ?string
    {
        if (!empty($_POST['save']) && !empty($_POST['dcstore_pattern'])) {
            $this->setSettings('pattern', (string) $_POST['dcstore_pattern']);
            $this->redirect($url);
        }

        return
        '<p class="info">' . __('File will be overwritten if it exists') . '</p>' .
        '<p><label class="classic" for="dcstore_pattern">' .
        __('Predictable URL to zip file on the external repository') . '<br />' .
        form::field('dcstore_pattern', 160, 255, $this->pattern) . '</label>' .
        '</p>' .
        '<p class="form-note">' .
        sprintf(__('You can use wildcards %s'), '%author%, %type%, %id%, %version%.') .
        '<br /> ' .
        __('For exemple on github https://github.com/MyGitName/%id%/releases/download/v%version%/%type%-%id%.zip') .
        '<br />' .
        __('Note on github, you must create a release and join to it the module zip file.') . '
        </p>';
    }

    public function openModule(): ?bool
    {
        $content = $this->generateXML();
        if ($this->hasError()) {
            return false;
        }

        $content = $this->prettyXML($content);

        try {
            files::putContent($this->module['sroot'] . '/dcstore.xml', $content);
            $this->setSuccess(__('Write dcstore.xml file.'));
        } catch (Exception $e) {
            $this->setError(__('Failed to write dcstore.xml file'));

            return false;
        }

        return true;
    }

    public function generateXML(): string
    {
        $xml = ['<modules xmlns:da="http://dotaddict.org/da/">'];
        $rsp = new xmlTag('module');

        # id
        if (empty($this->module['id'])) {
            $this->setError(__('unkow module id'));
        }
        $rsp->id = $this->module['id'];

        # name
        if (empty($this->module['oname'])) {
            $this->setError(__('unknow module name'));
        }
        $rsp->name($this->module['oname']);

        # version
        if (empty($this->module['version'])) {
            $this->setError(__('unknow module version'));
        }
        $rsp->version($this->module['version']);

        # author
        if (empty($this->module['author'])) {
            $this->setError(__('unknow module author'));
        }
        $rsp->author($this->module['author']);

        # desc
        if (empty($this->module['desc'])) {
            $this->setError(__('unknow module description'));
        }
        $rsp->desc($this->module['desc']);

        # repository
        if (empty($this->module['repository'])) {
            $this->setError(__('no repository set in _define.php'));
        }

        # file
        $file_pattern = $this->parseFilePattern();
        if (empty($file_pattern)) {
            $this->setError(__('no zip file pattern set in configuration'));
        }
        $rsp->file($file_pattern);

        # da dc_min or requires core
        if (!empty($this->module['requires']) && is_array($this->module['requires'])) {
            foreach ($this->module['requires'] as $req) {
                if (!is_array($req)) {
                    $req = [$req];
                }
                if ($req[0] == 'core') {
                    $this->module['dc_min'] = $req[1];

                    break;
                }
            }
        }
        if (empty($this->module['dc_min'])) {
            $this->setWarning(__('no minimum dotclear version'));
        } else {
            $rsp->insertNode(new xmlTag('da:dcmin', $this->module['dc_min']));
        }

        # da details
        if (empty($this->module['details'])) {
            $this->setWarning(__('no details URL'));
        } else {
            $rsp->insertNode(new xmlTag('da:details', $this->module['details']));
        }

        # da sshot
        //$rsp->insertNode(new xmlTag('da:sshot', $this->module['sshot']));

        # da section
        if (!empty($this->module['section'])) {
            $rsp->insertNode(new xmlTag('da:section', $this->module['section']));
        }

        # da support
        if (empty($this->module['support'])) {
            $this->setWarning(__('no support URL'));
        } else {
            $rsp->insertNode(new xmlTag('da:support', $this->module['support']));
        }

        # da tags
        //$rsp->insertNode(new xmlTag('da:tags', $this->module['tags']));

        $res = new xmlTag('modules', $rsp);
        $res->insertAttr('xmlns:da', 'http://dotaddict.org/da/');

        return self::prettyXML($res->toXML());
    }

    private static function prettyXML(string $str): string
    {
        if (class_exists('DOMDocument')) {
            $dom                     = new DOMDocument('1.0');
            $dom->preserveWhiteSpace = false;
            $dom->formatOutput       = true;
            $dom->loadXML($str);

            return (string) $dom->saveXML();
        }

        return str_replace('><', ">\n<", $str);
    }

    private function parseFilePattern(): string
    {
        return text::tidyURL(str_replace(
            [
                '%type%',
                '%id%',
                '%version%',
                '%author%',
            ],
            [
                $this->module['type'],
                $this->module['id'],
                $this->module['version'],
                $this->module['author'],
            ],
            $this->pattern
        ));
    }
}
