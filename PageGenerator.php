<?php
/**
 * Definition of class PageGenerator
 *
 * @copyright  2014-today Justso GmbH
 * @author     j.schirrmacher@justso.de
 * @package    justso\justgen
 */

namespace justso\justgen;

use justso\justapi\InvalidParameterException;
use justso\justapi\NotFoundException;
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

    private $forceSiteMapUpdate = false;

    public function __construct(SystemEnvironmentInterface $environment)
    {
        parent::__construct($environment);
    }

    /**
     * Service to generate a page
     */
    public function getAction()
    {
        $config = $this->environment->getBootstrap()->getConfiguration();
        $languages = $config['languages'];
        try {
            $this->extractParams($languages);
            $rule = $this->findMatchingPageRule();
            $content = $this->handlePageRule($rule, $languages);
            $this->updateSiteMap();
            $this->environment->sendResult("200 Ok", "text/html; charset=utf-8", $content);
        } catch (RedirectException $e) {
            $destination = $e->getMessage();
            $this->environment->sendHeader("Location: " . $destination);
            $this->environment->sendResult("301 Moved Permanently", 'text/plain', "Location: " . $destination);
        } catch (NotFoundException $e) {
            if (!empty($config['errorpages']['404'])) {
                $this->page = $config['errorpages']['404'];
                $this->defaultsApplied = false;
                $content = $this->handlePageRule($config['pages'][$this->page], $languages);
                $mime = 'text/html';
            } else {
                $content = "Page '{$this->page}' not found";
                $mime = 'text/plain';
            }
            $this->environment->sendResult("404 Not found", "$mime; charset=utf-8", $content);
        } catch (\Exception $e) {
            $this->environment->sendResult('500 Server Error', 'text/plain', $e->getMessage());
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
        $config = $this->environment->getBootstrap()->getConfiguration();
        $ruleMatcher = new RuleMatcher($config['pages']);
        $entry = $ruleMatcher->find($this->page);
        if ($entry !== null) {
            return $entry;
        }
        throw new NotFoundException('No page rule defined');
    }

    /**
     * Extracts language and page parameters from access path
     *
     * @param $languages
     * @throws \justso\justapi\InvalidParameterException
     */
    private function extractParams($languages)
    {
        $config = $this->environment->getBootstrap()->getConfiguration();
        preg_match('/^\/?(..)?\/([^\?]*)/', $this->getURI(), $parts);

        $this->language = $languages[0];
        $fallback = !empty($config['fallbackForUnknownLanguage']);
        if (empty($parts[1]) || !in_array($parts[1], $languages) && $fallback) {
            $this->defaultsApplied = true;
        } else {
            if (!in_array($parts[1], $languages)) {
                throw new InvalidParameterException("Unknown language");
            }
            $this->language = $parts[1];
        }
        if (empty($parts[2])) {
            $this->defaultsApplied = true;
        } else {
            $this->page = preg_replace('/(\.html|\/)$/', '', $parts[2]);
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

    private function getURI()
    {
        $server = $this->environment->getRequestHelper()->getServerParams();
        if (empty($server['REQUEST_URI'])) {
            throw new InvalidParameterException("Missing path info");
        }
        return $server['REQUEST_URI'];
    }

    /**
     * Stores the content as a file for improved access performance for later requests.
     *
     * @param $content
     */
    private function storeContent($content)
    {
        $appRoot = $this->environment->getBootstrap()->getAppRoot();
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
     * @return string  content of page
     * @throws RedirectException
     */
    public function handlePageRule($rule, $languages, $okCode = '200 Ok')
    {
        $dynamic = strpos($rule, 'dynamic:') === 0;
        $develop = $this->environment->getBootstrap()->getInstallationType() === 'development';
        $template = $dynamic ? substr($rule, strlen('dynamic:')) : $rule;
        $content = $this->generate($template, $languages);
        if (!$dynamic && !$develop) {
            $this->storeContent($content);
        }
        if ($this->defaultsApplied && !$dynamic) {
            throw new RedirectException('/' . $this->language . '/' . $this->page);
        }
        return $content;
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
        $rawParams = $this->environment->getRequestHelper()->getAllParams();
        $params = array_filter($rawParams, function () use (&$rawParams) {
            $key = key($rawParams);
            next($rawParams);
            return strpos($key, '_') !== 0;
        });
        $templateRoot = $this->environment->getBootstrap()->getAppRoot() . '/templates';
        $pageTemplate = new PageTemplate($templateRoot, $templateName . '.tpl', $languages, $params);
        return $pageTemplate->generate($this->language, $this->page, $this->environment);
    }

    /**
     * Updates the sitemap.xml file
     */
    private function updateSiteMap()
    {
        $bootstrap = $this->environment->getBootstrap();
        if ($this->forceSiteMapUpdate || $bootstrap->getInstallationType() === 'production') {
            $url = $bootstrap->getWebAppUrl() . '/' . $this->language . '/' . $this->page;
            $fs = $this->environment->getFileSystem();
            $now = (new \DateTime())->format(\DateTime::W3C);
            $fileName = $bootstrap->getAppRoot() . '/htdocs/sitemap.xml';
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
            $dom = new \DOMDocument("1.0");
            $dom->preserveWhiteSpace = false;
            $dom->formatOutput = true;
            $dom->loadXML($sitemap->asXML());
            $fs->putFile($fileName, $dom->saveXML());
        }
    }

    public function enforceSiteMapUpdate()
    {
        $this->forceSiteMapUpdate = true;
    }
}
