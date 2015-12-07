<?php
/**
 * Definition of class PageGeneratorTest
 *
 * @copyright  2014-today Justso GmbH
 * @author     j.schirrmacher@justso.de
 * @package    justso\justgen\test
 */

namespace justso\justgen\test;

use justso\justapi\Bootstrap;
use justso\justapi\FileSystemInterface;
use justso\justapi\testutil\ServiceTestBase;
use justso\justapi\testutil\FileSystemSandbox;
use justso\justapi\testutil\TestEnvironment;
use justso\justgen\PageGenerator;

/**
 * Class PageGeneratorTest
 * @package justso\justgen\test
 */
class PageGeneratorTest extends ServiceTestBase
{
    protected function setUp()
    {
        parent::setUp();
        $config = array(
            'environments' => array('test' => array(
                'approot' => '/test-root',
                'appurl' => 'http://localhost',
            )),
            'languages' => array('de'),
            'pages' => array(
                'index' => 'testTemplate',
                'error404' => 'errorTemplate',
            ),
            'redirects' => array(
                'index2' => 'index',
            ),
        );
        Bootstrap::getInstance()->setTestConfiguration('/test-root', $config);
    }

    protected function tearDown()
    {
        parent::tearDown();
        Bootstrap::getInstance()->resetConfiguration();
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
        $env = $this->createTestEnvironment();
        $env->getRequestHelper()->set(array(), $this->createServerParams('de/index.html'));
        $this->setupPageFiles($env);

        $service = new PageGenerator($env);
        $service->getAction();

        $header = array(
            'HTTP/1.0 200 Ok',
            'Content-Type: text/html; charset=utf-8',
        );
        $this->assertEquals($header, $env->getResponseHeader());
        $this->assertSame('prefix Hallo Welt! postfix', $env->getResponseContent());
    }

    public function testAccessNonExistingPage($type = 'text/plain', $msg = "Page 'non-existing' not found")
    {
        $env = $this->createTestEnvironment();
        $env->getRequestHelper()->set(array(), $this->createServerParams('de/non-existing.html'));
        $this->setupPageFiles($env);

        $service = new PageGenerator($env);
        $service->getAction();

        $header = array(
            'HTTP/1.0 404 Not found',
            'Content-Type: ' . $type . '; charset=utf-8',
        );
        $this->assertEquals($header, $env->getResponseHeader());
        $this->assertSame($msg, $env->getResponseContent());
    }

    public function testCustomErrorPage()
    {
        $bootstrap = Bootstrap::getInstance();
        $config = $bootstrap->getConfiguration();
        $config['errorpages'] = array('404' => 'error404');
        $bootstrap->setTestConfiguration('/test-root', $config);
        $this->testAccessNonExistingPage('text/html', 'custom error page');
    }

    public function testAccessWithDefaults()
    {
        $env = $this->createTestEnvironment();
        $env->getRequestHelper()->set(array(), $this->createServerParams(''));
        $this->setupPageFiles($env);

        $service = new PageGenerator($env);
        $service->getAction();

        $header = array(
            'Location: /de/index',
            'HTTP/1.0 301 Moved Permanently',
            'Content-Type: text/plain'
        );
        $this->assertEquals($header, $env->getResponseHeader());
    }

    public function testRedirects()
    {
        $env = $this->createTestEnvironment();
        $env->getRequestHelper()->set(array(), $this->createServerParams('de/index2.html'));
        $this->setupPageFiles($env);

        $service = new PageGenerator($env);
        $service->getAction();

        $header = array(
            'Location: /de/index',
            'HTTP/1.0 301 Moved Permanently',
            'Content-Type: text/plain',
        );
        $this->assertEquals($header, $env->getResponseHeader());
        $this->assertSame('Location: /de/index', $env->getResponseContent());
    }

    public function testUnknownLanguage()
    {
        $env = $this->createTestEnvironment();
        $env->getRequestHelper()->set(array(), $this->createServerParams('en/index.html'));
        $this->setupPageFiles($env);

        $service = new PageGenerator($env);
        $service->getAction();

        $header = array(
            'HTTP/1.0 500 Server Error',
            'Content-Type: text/plain',
        );
        $this->assertEquals($header, $env->getResponseHeader());
        $this->assertSame("Unknown language", $env->getResponseContent());
    }

    public function testSiteMap()
    {
        $env = $this->createTestEnvironment();
        $env->getRequestHelper()->set(array(), $this->createServerParams('de/index.html'));
        /** @var FileSystemSandbox $fs */
        $fs = $this->setupPageFiles($env);
        $sitemapFile = '/test-root/htdocs/sitemap.xml';
        $fs->putFile($sitemapFile, file_get_contents(__DIR__ . '/sitemap.xml'));

        $service = new PageGenerator($env, 'test');
        $service->getAction();
        $this->assertTrue(in_array('putFile(' . $sitemapFile . ')', $fs->getProtocol()));
        $sitemap = $fs->getFile($sitemapFile);
        $this->assertSame(1, preg_match('/<loc>http:\/\/localhost\/de\/index<\/loc>/', $sitemap));
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

    public function testHTTPS() {
        $env = $this->createTestEnvironment();
        $env->getRequestHelper()->set(array(), $this->createServerParams('de/index.html', true));
        $this->setupPageFiles($env, '{$base_url}');

        $service = new PageGenerator($env);
        $service->getAction();

        $header = array(
            'HTTP/1.0 200 Ok',
            'Content-Type: text/html; charset=utf-8',
        );
        $this->assertEquals($header, $env->getResponseHeader());
        $this->assertSame('https://example.com', $env->getResponseContent());
    }
}
