<?php
/**
 * Definition of class FlushCacheTest
 *
 * @copyright  2014-today Justso GmbH
 * @author     j.schirrmacher@justso.de
 * @package    justso\justgen\test
 */

namespace justso\justgen\test;

use justso\justapi\Bootstrap;
use justso\justapi\test\FileSystemSandbox;
use justso\justapi\test\ServiceTestBase;
use justso\justgen\FlushCache;

/**
 * Class FlushCacheTest
 * @package justso\justgen\test
 */
class FlushCacheTest extends ServiceTestBase
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

    public function testGetAction()
    {
        $env = $this->createTestEnvironment();
        /** @var FileSystemSandbox $fs */
        $fs = $env->getFileSystem();
        $fs->putFile('/test-root/htdocs/de/index.html', 'German content');
        $fs->putFile('/test-root/htdocs/other/index.html', 'Other content');
        $fs->resetProtocol();

        $service = new FlushCache($env);
        $service->getAction();
        $this->assertJSONHeader($env);
        $this->assertSame('"ok"', $env->getResponseContent());
        $this->assertEquals(array(
            'deleteFile(/test-root/htdocs/de/index.html)',
            'removeDir(/test-root/htdocs/de)',
        ), $fs->getProtocol());
    }
}
