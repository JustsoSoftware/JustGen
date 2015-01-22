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
        if ($this->defaultsApplied) {
            $url = '/' . $this->language . '/' . $this->page;
            $this->environment->sendHeader('Location: ' . $url);
            $this->environment->sendResult("301 Moved Permanently", 'text/plain', "New location: $url");
        } else {
            try {
                list($type, $info) = $this->findMatchingPageRule();
                $this->handlePageRule($type, $info, $languages);
            } catch (\Exception $e) {
                $this->environment->sendResult(
                    '404 Not found',
                    "text/plain; charset=utf-8",
                    "Page '{$this->page}' not found"
                );
            }
        }
    }

    /**
     * @return array
     * @throws \Exception
     */
    private function findMatchingPageRule()
    {
        $config = Bootstrap::getInstance()->getConfiguration();
        foreach ($config['pages'] as $page => $rule) {
            $pattern = str_replace(array('/', '*', '%d'), array('\\/', '.*', '\d'), $page);
            if (preg_match('/^' . $pattern . '$/', $this->page)) {
                list($type, $info) = array_pad(explode(":", $rule, 2), 2, null);
                if ($info === null) {
                    $info = $type;
                    $type = 'template';
                }
                return array($type, $info);
            }
        }
        throw new \Exception('No page rule defined');
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
        preg_match('/^\/?(..)?\/(.*)?/', $server['REDIRECT_URL'], $parts);

        $this->language = $languages[0];
        if ($parts[1] == '') {
            $this->defaultsApplied = true;
        } else {
            $this->language = $parts[1];
            if (!in_array($this->language, $languages)) {
                throw new InvalidParameterException("Unknown language");
            }
        }
        if (empty($parts[2])) {
            $this->defaultsApplied = true;
        } else {
            $this->page = preg_replace('/\.html$/', '', $parts[2]);
        }
    }

    /**
     * @param $content
     */
    private function storeContent($content)
    {
        $appRoot = Bootstrap::getInstance()->getAppRoot();
        $destination = $appRoot . '/htdocs/' . $this->language . '/' . $this->page . '.html';
        $fs = $this->environment->getFileSystem();
        $fs->putFile($destination, $content);

        if ($this->defaultsApplied) {
            $this->environment->sendHeader('Location: /' . $this->language . '/' . $this->page);
        } else {
            $this->environment->sendResult("200 Ok", "text/html; charset=utf-8", $content);
        }
    }

    /**
     * @param $type
     * @param $info
     * @param $languages
     */
    private function handlePageRule($type, $info, $languages)
    {
        switch ($type) {
            case 'template':
                $server = $this->environment->getRequestHelper()->getServerParams();
                $baseUrl = 'http://' . $server['HTTP_HOST'];
                $pageTemplate = new PageTemplate($info, $languages, $baseUrl);
                $fs = $this->environment->getFileSystem();
                $content = $pageTemplate->generate($this->language, $this->page, $fs);
                $this->storeContent($content);
                break;

            case 'redirect':
                $this->environment->sendHeader('Location: /' . $this->language . '/' . $info);
                $this->environment->sendResult("301 Moved Permanently", 'text/plain', "Location: $info");
                break;

            default:
                $this->environment->sendResult(
                    "500 Server Error",
                    "text/plain; charset=utf-8",
                    "Config Error: undefined rule type '$type'"
                );
                break;
        }
    }
}
