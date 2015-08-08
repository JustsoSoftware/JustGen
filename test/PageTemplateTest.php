<?php
/**
 * Definition of class PageTemplateTest
 *
 * @copyright  2014-today Justso GmbH
 * @author     j.schirrmacher@justso.de
 * @package    justso\justgen\test
 */

namespace justso\justgen\test;

use justso\justapi\Bootstrap;
use justso\justapi\test\FileSystemSandbox;
use justso\justgen\PageTemplate;

/**
 * Class PageTemplateTest
 * @package justso\justgen\test
 */
class PageTemplateTest extends \PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        parent::setUp();
        $config = array(
            'environments' => array('test' => array('approot' => '/test-root')),
            'languages' => array('de'),
            'pages' => array('abc' => 'testTemplate')
        );
        Bootstrap::getInstance()->setTestConfiguration('/test-root', $config);
    }

    protected function tearDown()
    {
        parent::tearDown();
        Bootstrap::getInstance()->resetConfiguration();
    }

    /**
     * Tests the generate() method of the PageTemplate.
     */
    public function testGenerate()
    {
        $fs = new FileSystemSandbox();
        $fs->putFile('/test-root/templates/testTemplate.tpl', 'prefix {$test} postfix');
        $fs->putFile('/test-root/htdocs/nls/abc.js', 'define({"root": {"test": "Hallo Welt!"}});');
        $template = new PageTemplate('testTemplate', array('de'), '/test-root');
        $result = $template->generate('de', 'abc', $fs);
        $this->assertSame('prefix Hallo Welt! postfix', $result);
    }
}
