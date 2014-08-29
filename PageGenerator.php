<?php
/**
 * Definition of class PageGenerator
 * 
 * @copyright  2014-today Justso GmbH
 * @author     j.schirrmacher@justso.de
 * @package    justso\justgen
 */

namespace justso\justgen;

use justso\justapi\Bootstrap;
use justso\justapi\FileSystem;
use justso\justapi\InvalidParameterException;
use justso\justapi\RestService;
use justso\justtexts\model\Text;

require dirname(dirname(__DIR__)) . '/smarty-3.1.19/Smarty.class.php';

/**
 * Class PageGenerator
 *
 * @package justso\justgen
 */
class PageGenerator extends RestService
{
    function getAction()
    {
        $defaultsApplied = false;
        $config = Bootstrap::getInstance()->getConfiguration();
        $languages = $config['languages'];
        $server = $this->environment->getRequestHelper()->getServerParams();
        $parts = explode('/', $server['REDIRECT_URL'], 2);

        if ($parts[0] == '') {
            $language = $languages[0];
            $defaultsApplied = true;
        } else {
            $language = $parts[0];
            if (!in_array($language, $languages)) {
                throw new InvalidParameterException("Unknown language");
            }
        }
        if ($parts[1] == '') {
            $page = 'index';
            $defaultsApplied = true;
        } else {
            $page = basename($parts[1], '.html');
        }
        if (!isset($config['pages'][$page])) {
            $this->environment->sendResult('404 Not found', "text/plain; charset=utf-8", "Page not found");
        } else {
            $template = $config['pages'][$page];
            $appRoot = Bootstrap::getInstance()->getAppRoot();
            $smarty = new \Smarty;
            $smarty->setTemplateDir($appRoot . '/content/templates');
            $smarty->setCompileDir($appRoot . '/files/smarty');
            $smarty->assign('language', $language);
            $smarty->assign('page', $page);

            $fs = new FileSystem();
            $pageTexts = new Text($fs, $page, $appRoot, $languages);
            $smarty->assign(array_map(
                function($info) {
                    return $info['content'];
                },
                $pageTexts->getPageTexts($language)
            ));

            $content = $smarty->fetch($template . '.tpl');
            $destinationFolder = $appRoot . '/htdocs/' . $language;
            $destination = $destinationFolder . '/' . $page . '.html';
            if (!file_exists($destinationFolder)) {
                mkdir($destinationFolder);
            }
            file_put_contents($destination, $content);
            if ($defaultsApplied) {
                $this->environment->sendHeader('Location: /' . $language . '/' . $page . '.html');
            } else {
                $this->environment->sendResult("200 Ok", "text/html", $content);
            }
        }
    }
}
