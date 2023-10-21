<?php

declare(strict_types=1);

namespace Dotclear\Plugin\improve\Task;

use DOMDocument;
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
use Dotclear\Helper\Html\XmlTag;
use Dotclear\Helper\Text;
use Dotclear\Plugin\improve\{
    Task,
    TaskDescriptor
};
use Exception;

/**
 * @brief       improve task: dcstore class.
 * @ingroup     improve
 *
 * @author      Jean-Christian Denis
 * @copyright   GPL-2.0 https://www.gnu.org/licenses/gpl-2.0.html
 */
class DcStore extends Task
{
    /**
     * Settings dcstore zip url pattern.
     *
     * @var     string  $pattern
     */
    private $pattern = '';

    protected function getProperties(): TaskDescriptor
    {
        return new TaskDescriptor(
            id: 'dcstore',
            name: __('Store file'),
            description: __('Re-create dcstore.xml file according to _define.php variables'),
            configurator: true,
            types: ['plugin', 'theme'],
            priority: 420
        );
    }

    protected function init(): bool
    {
        $pattern       = $this->settings->get('pattern');
        $this->pattern = is_string($pattern) ? $pattern : '';

        return true;
    }

    public function isConfigured(): bool
    {
        return !empty($this->settings->get('pattern'));
    }

    public function configure(string $url): string
    {
        if (!empty($_POST['save']) && !empty($_POST['dcstore_pattern'])) {
            $this->settings->set('pattern', (string) $_POST['dcstore_pattern']);
            $this->redirect($url);
        }

        return (new Div())->items([
            (new Note())->text(__('File will be overwritten if it exists'))->class('form-note'),
            (new Fieldset())->class('fieldset')->legend((new Legend(__('Contents'))))->fields([
                // phpexe_path
                (new Para())->items([
                    (new Label(__('Predictable URL to zip file on the external repository:'), Label::OUTSIDE_LABEL_BEFORE))->for('dcstore_pattern'),
                    (new Input('dcstore_pattern'))->size(65)->maxlenght(255)->value($this->pattern),
                ]),
                (new Note())->text(sprintf(__('You can use wildcards %s'), '%author%, %type%, %id%, %version%.'))->class('form-note'),
                (new Note())->text(__('For exemple on github https://github.com/MyGitName/%id%/releases/download/v%version%/%type%-%id%.zip'))->class('form-note'),
                (new Note())->text(__('Note on github, you must create a release and join to it the module zip file.'))->class('form-note'),
            ]),
        ])->render();
    }

    public function openModule(): ?bool
    {
        $content = $this->generateXML();
        if (!$this->error->empty()) {
            return false;
        }

        $content = $this->prettyXML($content);

        try {
            Files::putContent($this->module->get('root') . DIRECTORY_SEPARATOR . 'dcstore.xml', $content);
            $this->success->add(__('Write dcstore.xml file.'));
        } catch (Exception $e) {
            $this->error->add(__('Failed to write dcstore.xml file'));

            return false;
        }

        return true;
    }

    /**
     * Generate dcstore XML content.
     *
     * @return  string  The XML content
     */
    public function generateXML(): string
    {
        $xml = ['<modules xmlns:da="http://dotaddict.org/da/">'];
        $rsp = new XmlTag('module');

        # id
        $rsp->insertAttr('id', $this->module->getId());

        # name
        if (empty($this->module->get('name'))) {
            $this->error->add(__('unknow module name'));
        }
        $rsp->insertNode(new XmlTag('name', $this->module->get('name')));

        # version
        if (empty($this->module->get('version'))) {
            $this->error->add(__('unknow module version'));
        }
        $rsp->insertNode(new XmlTag('version', $this->module->get('version')));

        # author
        if (empty($this->module->get('author'))) {
            $this->error->add(__('unknow module author'));
        }
        $rsp->insertNode(new XmlTag('author', $this->module->get('author')));

        # desc
        if (empty($this->module->get('desc'))) {
            $this->error->add(__('unknow module description'));
        }
        $rsp->insertNode(new XmlTag('desc', $this->module->get('desc')));

        # repository
        if (empty($this->module->get('repository'))) {
            $this->error->add(__('no repository set in _define.php'));
        }

        # file
        $file_pattern = $this->parseFilePattern();
        if (empty($file_pattern)) {
            $this->error->add(__('no zip file pattern set in configuration'));
        }
        $rsp->insertNode(new XmlTag('file', $file_pattern));

        # da dc_min or requires core
        if (!empty($this->module->get('requires')) && is_array($this->module->get('requires'))) {
            foreach ($this->module->get('requires') as $req) {
                if (!is_array($req)) {
                    $req = [$req];
                }
                if ($req[0] == 'core') {
                    $this->module->set('dc_min', $req[1]);

                    break;
                }
            }
        }
        if (empty($this->module->get('dc_min'))) {
            $this->warning->add(__('no minimum dotclear version'));
        } else {
            $rsp->insertNode(new XmlTag('da:dcmin', $this->module->get('dc_min')));
        }

        # da details
        if (empty($this->module->get('details'))) {
            $this->warning->add(__('no details URL'));
        } else {
            $rsp->insertNode(new XmlTag('da:details', $this->module->get('details')));
        }

        # da sshot
        //$rsp->insertNode(new XmlTag('da:sshot', $this->module['sshot']));

        # da section
        if (!empty($this->module->get('section'))) {
            $rsp->insertNode(new XmlTag('da:section', $this->module->get('section')));
        }

        # da support
        if (empty($this->module->get('support'))) {
            $this->warning->add(__('no support URL'));
        } else {
            $rsp->insertNode(new XmlTag('da:support', $this->module->get('support')));
        }

        # da tags
        //$rsp->insertNode(new XmlTag('da:tags', $this->module['tags']));

        $res = new XmlTag('modules', $rsp);
        $res->insertAttr('xmlns:da', 'http://dotaddict.org/da/');

        return self::prettyXML($res->toXML());
    }

    /**
     * Clean up XML content.
     *
     * @return  string  The pretty XML content
     */
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

    /**
     * Parse module info.
     *
     * @return  string  The parsed content
     */
    private function parseFilePattern(): string
    {
        return Text::tidyURL(str_replace(
            [
                '%type%',
                '%id%',
                '%version%',
                '%author%',
            ],
            [
                $this->module->get('type'),
                $this->module->getId(),
                $this->module->get('version'),
                $this->module->get('author'),
            ],
            $this->pattern
        ));
    }
}
