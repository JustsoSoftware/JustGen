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
use justso\justapi\SystemEnvironmentInterface;

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
        try {
            $config = Bootstrap::getInstance()->getConfiguration();
            $languages = $config['languages'];
            $this->extractParams($languages);
            if ($this->defaultsApplied) {
                throw new RedirectException('/' . $this->language . '/' . $this->page);
            } else {
                $this->checkRedirections();
                $rule = $this->findMatchingPageRule();
                $this->handlePageRule($rule, $languages);
            }
        } catch (RedirectException $e) {
            $destination = $e->getMessage();
            $this->environment->sendHeader("Location: " . $destination);
            $this->environment->sendResult("301 Moved Permanently", 'text/plain', "Location: " . $destination);
        } catch (\SmartyCompilerException $e) {
            $this->environment->sendResult('500 Server Error', 'text/plain', $e->getMessage());
        } catch (\Exception $e) {
            $this->environment->sendResult('404 Not found', 'text/plain', "Page '{$this->page}' not found");
        }
    }

    /**
     * Searches in $where for an entry matching the pattern in the key part.
     *
     * @param string[] $where
     * @return string|null
     */
    private function findMatchingEntry(array $where)
    {
        foreach ($where as $name => $entry) {
            $pattern = str_replace(array('/', '*', '%d'), array('\\/', '.*', '\d'), $name);
            if (preg_match('/^' . $pattern . '$/', $this->page)) {
                return $entry;
            }
        }
        return null;
    }

    /**
     * Checks redirection rules and throws a RedirectException if one of them applies.
     *
     * @throws RedirectException
     */
    private function checkRedirections()
    {
        $config = Bootstrap::getInstance()->getConfiguration();
        if (isset($config['redirects'])) {
            $entry = $this->findMatchingEntry($config['redirects']);
            if ($this->findMatchingEntry($config['redirects']) !== null) {
                throw new RedirectException($entry);
            }
        }
    }

    /**
     * Searches for a matching page rule and returns the template name.
     *
     * @return string Template name
     * @throws \Exception
     */
    private function findMatchingPageRule()
    {
        $config = Bootstrap::getInstance()->getConfiguration();
        $entry = $this->findMatchingEntry($config['pages']);
        if ($entry !== null) {
            return $entry;
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
        if (empty($parts[1])) {
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
            $this->page = preg_replace('/(\.html|\/)$/', '', $parts[2]);
        }
    }

    /**
     * Stores the content as a file for improved access performance for later requests.
     *
     * @param $content
     */
    private function storeContent($content)
    {
        $appRoot = Bootstrap::getInstance()->getAppRoot();
        $destination = $appRoot . '/htdocs/' . $this->language . '/' . $this->page . '.html';
        $fs = $this->environment->getFileSystem();
        $fs->putFile($destination, $content);
    }

    /**
     * Handles the page rule by generating the content, caching it and sending the result.
     *
     * @param string   $info
     * @param string[] $languages
     */
    private function handlePageRule($info, $languages)
    {
        $templateName = $info;
        if (strpos($info, 'dynamic:') === 0) {
            $templateName = substr($info, strlen('dynamic:'));
            $cacheResults = false;
        } else {
            $cacheResults = Bootstrap::getInstance()->getInstallationType() != 'development';
        }
        $content = $this->generate($templateName, $languages);
        if ($cacheResults) {
            $this->storeContent($content);
        }
        $this->environment->sendResult("200 Ok", "text/html; charset=utf-8", $content);
    }

    /**
     * Generates the page by applying parameters, variables and texts to the template.
     *
     * @param string   $templateName
     * @param string[] $languages
     * @return string
     */
    private function generate($templateName, $languages)
    {
        $server = $this->environment->getRequestHelper()->getServerParams();
        $baseUrl = 'http://' . $server['HTTP_HOST'];
        $rawParams = $this->environment->getRequestHelper()->getAllParams();
        $params = array_filter($rawParams, function() use (&$rawParams) {
            $key = key($rawParams);
            next($rawParams);
            return strpos($key, '_') !== 0;
        });
        $pageTemplate = new PageTemplate($templateName, $languages, $baseUrl, $params);
        $fs = $this->environment->getFileSystem();
        $content = $pageTemplate->generate($this->language, $this->page, $fs);
        return $content;
    }
}
