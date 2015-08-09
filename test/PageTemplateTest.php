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
        $fs = $this->setupFileSystem();
        $template = new PageTemplate('testTemplate', array('de'), '/test-root');
        $result = $template->generate('de', 'abc', $fs);
        $this->assertSame('prefix Hallo Welt! postfix', $result);
    }

    public function testWithProcessor()
    {
        $fs = $this->setupFileSystem();
        $template = new PageTemplate('testTemplate', array('de'), '/test-root');
        $fs->putFile('/test-root/processors/testTemplate.php', file_get_contents(__DIR__ . '/Processor.php'));
        $result = $template->generate('de', 'abc', $fs);
        $this->assertSame('Processed', $result);
    }

    public function testGetSmartyVars()
    {
        $fs = $this->setupFileSystem();
        $template = new PageTemplate('testTemplate', array('de'), '/test-root');
        $result = $template->getSmartyVars($fs, 'abc');
        $this->assertSame(array('test'), $result);
    }

    /**
     * @return FileSystemSandbox
     */
    private function setupFileSystem()
    {
        $fs = new FileSystemSandbox();
        $fs->putFile('/test-root/templates/testTemplate.tpl', 'prefix {$test} postfix');
        $fs->putFile('/test-root/htdocs/nls/abc.js', 'define({"root": {"test": "Hallo Welt!"}});');
        $fs->putFile('/test-root/smartyPlugins/testPlugin.php', '');
        return $fs;
    }
}
