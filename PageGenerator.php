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
        try {
            $this->extractParams($languages);
            $rule = $this->findMatchingPageRule();
            $this->handlePageRule($rule, $languages);
        } catch (RedirectException $e) {
            $destination = $e->getMessage();
            $this->environment->sendHeader("Location: " . $destination);
            $this->environment->sendResult("301 Moved Permanently", 'text/plain', "Location: " . $destination);
        } catch (\SmartyCompilerException $e) {
            $this->environment->sendResult('500 Server Error', 'text/plain', $e->getMessage());
        } catch (\Exception $e) {
            if (!empty($config['errorpages']['404'])) {
                $this->page = $config['errorpages']['404'];
                $this->defaultsApplied = false;
                $this->handlePageRule($config['pages'][$this->page], $languages, "404 Not found");
            } else {
                $this->environment->sendResult('404 Not found', 'text/plain', "Page '{$this->page}' not found");
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
        $ruleMatcher = new RuleMatcher($config['pages']);
        $entry = $ruleMatcher->find($this->page);
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
            $config = Bootstrap::getInstance()->getConfiguration();
            if (isset($config['redirects'])) {
                $ruleMatcher = new RuleMatcher($config['redirects']);
                $entry = $ruleMatcher->find($this->page);
                if ($entry !== null) {
                    $this->page = $entry;
                    $this->defaultsApplied = true;
                }
            }
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
     * @param string   $rule
     * @param string[] $languages
     * @param string   $okCode
     * @throws RedirectException
     */
    public function handlePageRule($rule, $languages, $okCode = '200 Ok')
    {
        $dynamic = strpos($rule, 'dynamic:') === 0;
        $develop = Bootstrap::getInstance()->getInstallationType() === 'development';
        $template = $dynamic ? substr($rule, strlen('dynamic:')) : $rule;
        $content = $this->generate($template, $languages);
        if (!$dynamic && !$develop) {
            $this->storeContent($content);
        }
        if ($this->defaultsApplied && !$dynamic) {
            throw new RedirectException('/' . $this->language . '/' . $this->page);
        } else {
            if (!$develop) {
                $this->updateSiteMap();
            }
            $this->environment->sendResult($okCode, "text/html; charset=utf-8", $content);
        }
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
        return $pageTemplate->generate($this->language, $this->page, $fs);
    }

    /**
     * Updates the sitemap.xml file
     */
    private function updateSiteMap()
    {
        $config = Bootstrap::getInstance()->getConfiguration();
        $url = $config['environments']['production']['appurl'] . '/' . $this->language . '/' . $this->page;
        $fs = $this->environment->getFileSystem();
        $now = (new \DateTime())->format(\DateTime::W3C);
        $fileName = Bootstrap::getInstance()->getAppRoot() . '/htdocs/sitemap.xml';
        $sitemap = new \SimpleXMLElement($fs->getFile($fileName));
        $found = false;
        foreach ($sitemap->url as $page) {
            if ($page->loc == $url) {
                $page->lastmod = $now;
                $found = true;
                break;
            }
        }
        if (!$found) {
            $page = $sitemap->addChild('url');
            $page->loc = $url;
            $page->lastmod = $now;
            $page->changefreq = 'daily';
            $page->priority = '0.6';
        }
        $fs->putFile($fileName, $sitemap->saveXML());
    }
}
