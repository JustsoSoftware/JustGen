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
use justso\justapi\InvalidParameterException;
use justso\justapi\RestService;

/**
 * Class PageGenerator
 *
 * @package justso\justgen
 */
class PageGenerator extends RestService
{
    private $defaultsApplied = false;

    private $language;

    private $page = 'index';

    /**
     * Service to generate a page
     */
    function getAction()
    {
        $config = Bootstrap::getInstance()->getConfiguration();
        $languages = $config['languages'];
        $this->extractParams($languages);
        if (!isset($config['pages'][$this->page])) {
            $this->environment->sendResult('404 Not found', "text/plain; charset=utf-8", "Page not found");
        } else {
            $fs = $this->environment->getFileSystem();
            $pageTemplate = new PageTemplate($config['pages'][$this->page], $languages);
            $content = $pageTemplate->generate($this->language, $this->page, $fs);
            $appRoot = Bootstrap::getInstance()->getAppRoot();
            $destination = $appRoot . '/htdocs/' . $this->language . '/' . $this->page . '.html';
            $fs->putFile($destination, $content);
            if ($this->defaultsApplied) {
                $this->environment->sendHeader('Location: /' . $this->language . '/' . $this->page . '.html');
            } else {
                $this->environment->sendResult("200 Ok", "text/html; charset=utf-8", $content);
            }
        }
    }

    /**
     * Extracts language and page parameters from access path
     *
     * @param $languages
     * @throws \justso\justapi\InvalidParameterException
     */
    private function extractParams($languages)
    {
        $server = $this->environment->getRequestHelper()->getServerParams();
        $parts = explode('/', $server['REDIRECT_URL'], 2);

        $this->language = $languages[0];
        if ($parts[0] == '') {
            $this->defaultsApplied = true;
        } else {
            $language = $parts[0];
            if (!in_array($language, $languages)) {
                throw new InvalidParameterException("Unknown language");
            }
        }
        if ($parts[1] == '') {
            $this->defaultsApplied = true;
        } else {
            $this->page = basename($parts[1], '.html');
        }
    }
}
