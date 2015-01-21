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
     * @param string $template
     * @param $languages
     */
    public function __construct($template, $languages, $baseUrl)
    {
        $this->template  = $template;
        $this->languages = $languages;
        $this->baseUrl   = $baseUrl;
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
