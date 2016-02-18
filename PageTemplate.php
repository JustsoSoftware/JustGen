<?php
/**
 * Definition of class PageTemplate
 *
 * @copyright  2014-today Justso GmbH
 * @author     j.schirrmacher@justso.de
 * @package    justso\justgen
 */

namespace justso\justgen;

use justso\justapi\Bootstrap;
use justso\justapi\FileSystemInterface;
use justso\justtexts\model\Text;

require dirname(dirname(__DIR__)) . '/autoload.php';

/**
 * Class PageTemplate
 * @package justso\justgen
 */
class PageTemplate
{
    /**
     * @var string
     */
    private $template;

    /**
     * @var string[]
     */
    private $languages;

    /**
     * @var string
     */
    private $baseUrl;

    /**
     * @var array
     */
    private $params;

    /**
     * @param string   $template
     * @param string[] $languages
     * @param string   $baseUrl
     * @param array    $params
     */
    public function __construct($template, $languages, $baseUrl, $params = array())
    {
        $this->template  = $template;
        $this->languages = $languages;
        $this->baseUrl   = $baseUrl;
        $this->params    = $params;
    }

    /**
     * Generate a page in the specified language.
     *
     * @param string $language
     * @param string $page
     * @param \justso\justapi\FileSystemInterface $fs
     * @return string
     */
    public function generate($language, $page, FileSystemInterface $fs)
    {
        $smarty = $this->setupSmarty($language, $page, $fs);
        $pageTexts = new Text($fs, $page, Bootstrap::getInstance()->getAppRoot(), $this->languages);
        $smarty->assign(array_map(
            function ($info) {
                return $info['content'];
            },
            $pageTexts->getPageTexts($language)
        ));

        $previous = set_error_handler(function () {
            return true;
        });
        $content = $smarty->fetch($this->template . '.tpl');
        set_error_handler($previous);

        $processorFile = Bootstrap::getInstance()->getAppRoot() . '/processors/' . $this->template . '.php';
        if ($fs->fileExists($processorFile)) {
            require_once($fs->getRealPath($processorFile));
            $processor = new $this->template($this->baseUrl);
            if ($processor instanceof ProcessorInterface) {
                $content = $processor->process($content, $page);
            }
        }

        return $content;
    }

    /**
     * Finds variables in template
     *
     * @param FileSystemInterface $fs   File system
     * @param string              $page Name of page
     * @return string[]                 Names of variables in template
     */
    public function getSmartyVars(FileSystemInterface $fs, $page)
    {
        $smarty = $this->setupSmarty($this->languages[0], $page, $fs);
        $vars = array();
        $previous = set_error_handler(function ($errNo, $errStr) use (&$vars) {
            if (preg_match('/Undefined index: (.+)/', $errStr, $matches)) {
                $vars[$matches[1]] = true;
            }
            return true;
        }, E_NOTICE);
        $smarty->fetch($this->template . '.tpl');
        set_error_handler($previous);
        return array_keys($vars);
    }

    /**
     * Sets up a smarty template.
     *
     * @param $language
     * @param $page
     * @param FileSystemInterface $fs
     * @return \Smarty
     */
    private function setupSmarty($language, $page, FileSystemInterface $fs)
    {
        $bootstrap = Bootstrap::getInstance();
        $appRoot = $bootstrap->getAppRoot();
        $smarty = new \Smarty;
        $template_dir = $fs->getRealPath($appRoot . '/templates');
        $smarty->setTemplateDir($template_dir);
        $smarty->setCompileDir($fs->getRealPath($appRoot . '/files/smarty'));
        if ($fs->fileExists($appRoot . '/smartyPlugins')) {
            $smarty->setPluginsDir($fs->getRealPath($appRoot . '/smartyPlugins'));
        }
        $smarty->assign('language', $language);
        $smarty->assign('page', $page);
        $smarty->assign('template_dir', $template_dir);
        $smarty->assign('base_url', $this->baseUrl);
        $smarty->assign('base_dir', $appRoot);
        $smarty->assign('params', http_build_query($this->params));
        $smarty->assign('instType', $bootstrap->getInstallationType());
        return $smarty;
    }
}
