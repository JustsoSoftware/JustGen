<?php
/**
 * Definition of class PageGeneratorTest
 *
 * @copyright  2014-today Justso GmbH
 * @author     j.schirrmacher@justso.de
 * @package    justso\justgen\test
 */

namespace justso\justgen;

use justso\justapi\FileSystemInterface;
use justso\justapi\testutil\ServiceTestBase;
use justso\justapi\testutil\FileSystemSandbox;
use justso\justapi\testutil\TestEnvironment;

/**
 * Class PageGeneratorTest
 * @package justso\justgen\test
 */
class PageGeneratorTest extends ServiceTestBase
{
    protected function setUp()
    {
        parent::setUp();
        $this->createTestEnvironment();
        $config = [
            'environments' => [
                'test' => [
                    'approot' => '/test-root',
                    'appurl' => 'https://example.com',
                ],
            ],
            'languages' => ['de'],
            'pages' => [
                'index' => 'testTemplate',
                'error404' => 'errorTemplate',
            ],
            'redirects' => [
                'index2' => 'index',
            ],
        ];
        $this->env->getBootstrap()->setTestConfiguration('/test-root', $config);
    }

    private function createServerParams($redirectUrl, $secure = false)
    {
        $params = [ 'HTTP_HOST' => 'example.com', 'REDIRECT_URL' => $redirectUrl ];
        if ($secure) {
            $params['HTTPS'] = 'on';
        }
        return $params;
    }

    public function testGetAction()
    {
        $this->env->getRequestHelper()->set(array(), $this->createServerParams('de/index.html'));
        $this->setupPageFiles($this->env);

        $service = new PageGenerator($this->env);
        $service->getAction();

        $header = array(
            'HTTP/1.0 200 Ok',
            'Content-Type: text/html; charset=utf-8',
        );
        $this->assertEquals($header, $this->env->getResponseHeader());
        $this->assertSame('prefix Hallo Welt! postfix', $this->env->getResponseContent());
    }

    public function testAccessNonExistingPage($type = 'text/plain', $msg = "Page 'non-existing' not found")
    {
        $this->env->getRequestHelper()->set(array(), $this->createServerParams('de/non-existing.html'));
        $this->setupPageFiles($this->env);

        $service = new PageGenerator($this->env);
        $service->getAction();

        $this->assertSame($msg, $this->env->getResponseContent());
        $header = array(
            'HTTP/1.0 404 Not found',
            'Content-Type: ' . $type . '; charset=utf-8',
        );
        $this->assertEquals($header, $this->env->getResponseHeader());
    }

    public function testCustomErrorPage()
    {
        $bootstrap = $this->env->getBootstrap();
        $config = $bootstrap->getConfiguration();
        $config['errorpages'] = array('404' => 'error404');
        $bootstrap->setTestConfiguration(null, $config);
        $this->testAccessNonExistingPage('text/html', 'custom error page');
    }

    public function testAccessWithDefaults()
    {
        $this->env->getRequestHelper()->set(array(), $this->createServerParams(''));
        $this->setupPageFiles($this->env);

        $service = new PageGenerator($this->env);
        $service->getAction();

        $this->assertSame('HTTP/1.0 301 Moved Permanently', $this->env->getResponseCode());
        $this->assertSame(['/de/index'], $this->env->getResponseHeaderEntries('Location'));
        $this->assertSame(['text/plain'], $this->env->getResponseHeaderEntries('Content-Type'));
    }

    public function testRedirects()
    {
        $this->env->getRequestHelper()->set(array(), $this->createServerParams('de/index2.html'));
        $this->setupPageFiles($this->env);

        $service = new PageGenerator($this->env);
        $service->getAction();

        $this->assertSame('HTTP/1.0 301 Moved Permanently', $this->env->getResponseCode());
        $header = $this->env->getResponseHeaderList();
        $this->assertSame(['/de/index'], $this->env->getResponseHeaderEntries('Location'));
        $this->assertSame(['text/plain'], $this->env->getResponseHeaderEntries('Content-Type'));
    }

    public function testUnknownLanguage()
    {
        $this->env->getRequestHelper()->set(array(), $this->createServerParams('en/index.html'));
        $this->setupPageFiles($this->env);

        $service = new PageGenerator($this->env);
        $service->getAction();

        $this->assertSame('HTTP/1.0 500 Server Error', $this->env->getResponseCode());
        $this->assertSame(['text/plain'], $this->env->getResponseHeaderEntries('Content-Type'));
        $this->assertSame("Unknown language", $this->env->getResponseContent());
    }

    public function testSiteMap()
    {
        $this->env->getRequestHelper()->set(array(), $this->createServerParams('de/index.html'));
        /** @var FileSystemSandbox $fs */
        $fs = $this->setupPageFiles($this->env);
        $sitemapFile = '/test-root/htdocs/sitemap.xml';
        $fs->putFile($sitemapFile, file_get_contents(__DIR__ . '/sitemap.xml'));

        $service = new PageGenerator($this->env);
        $service->enforceSiteMapUpdate();
        $service->getAction();
        $this->assertTrue(in_array('putFile(' . $sitemapFile . ')', $fs->getProtocol()));
        $sitemap = $fs->getFile($sitemapFile);
        $this->assertContains('<loc>https://example.com/de/index</loc>', $sitemap);
    }

    /**
     * @param TestEnvironment $env
     * @return FileSystemInterface
     */
    private function setupPageFiles(TestEnvironment $env, $template = 'prefix {$test} postfix')
    {
        $fs = $env->getFileSystem();
        $fs->putFile('/test-root/templates/testTemplate.tpl', $template);
        $fs->putFile('/test-root/templates/errorTemplate.tpl', 'custom error page');
        $fs->putFile('/test-root/htdocs/nls/index.js', 'define({"root": {"test": "Hallo Welt!"}});');
        return $fs;
    }

    public function testHTTPS()
    {
        $this->env->getRequestHelper()->set(array(), $this->createServerParams('de/index.html', true));
        $this->setupPageFiles($this->env, '{$base_url}');

        $service = new PageGenerator($this->env);
        $service->getAction();

        $header = array(
            'HTTP/1.0 200 Ok',
            'Content-Type: text/html; charset=utf-8',
        );
        $this->assertEquals($header, $this->env->getResponseHeader());
        $this->assertSame('https://example.com', $this->env->getResponseContent());
    }
}
