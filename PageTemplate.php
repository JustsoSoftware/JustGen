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
     * @param string $template
     * @param $languages
     */
    public function __construct($template, $languages)
    {
        $this->template  = $template;
        $this->languages = $languages;
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
        $smarty->setTemplateDir($fs->getRealPath($appRoot . '/templates'));
        $smarty->setCompileDir($fs->getRealPath($appRoot . '/files/smarty'));
        $smarty->assign('language', $language);
        $smarty->assign('page', $page);

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
