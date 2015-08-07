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
use justso\justapi\test\ServiceTestBase;
use justso\justapi\test\TestEnvironment;
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
            'environments' => array('test' => array('approot' => '/test-root')),
            'languages' => array('de'),
            'pages' => array('index' => 'testTemplate')
        );
        Bootstrap::getInstance()->setTestConfiguration('/test-root', $config);
    }

    protected function tearDown()
    {
        parent::tearDown();
        Bootstrap::getInstance()->resetConfiguration();
    }

    private function createServerParams($redirectUrl)
    {
        return array('HTTP_HOST' => 'example.com', 'REDIRECT_URL' => $redirectUrl);
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

    public function testAccessNonExistingPage()
    {
        $env = $this->createTestEnvironment();
        $env->getRequestHelper()->set(array(), $this->createServerParams('de/non-existing.html'));

        $service = new PageGenerator($env);
        $service->getAction();

        $header = array(
            'HTTP/1.0 404 Not found',
            'Content-Type: text/plain; charset=utf-8',
        );
        $this->assertEquals($header, $env->getResponseHeader());
        $this->assertSame("Page 'non-existing' not found", $env->getResponseContent());
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

    /**
     * @param TestEnvironment $env
     */
    private function setupPageFiles(TestEnvironment $env)
    {
        $fs = $env->getFileSystem();
        $fs->putFile('/test-root/templates/testTemplate.tpl', 'prefix {$test} postfix');
        $fs->putFile('/test-root/htdocs/nls/index.js', 'define({"root": {"test": "Hallo Welt!"}});');
    }
}
