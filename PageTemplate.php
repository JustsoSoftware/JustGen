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
        $appRoot = Bootstrap::getInstance()->getAppRoot();
        $smarty = new \Smarty;
        $template_dir = $fs->getRealPath($appRoot . '/templates');
        $smarty->setTemplateDir($template_dir);
        $smarty->setCompileDir($fs->getRealPath($appRoot . '/files/smarty'));
        $smarty->assign('language', $language);
        $smarty->assign('page', $page);
        $smarty->assign('template_dir', $template_dir);
        $smarty->assign('base_url', $this->baseUrl);
        $smarty->assign('params', http_build_query($this->params));

        $pageTexts = new Text($fs, $page, $appRoot, $this->languages);
        $smarty->assign(array_map(
            function($info) {
                return $info['content'];
            },
            $pageTexts->getPageTexts($language)
        ));

        $content = $smarty->fetch($this->template . '.tpl');
        return $content;
    }
}
