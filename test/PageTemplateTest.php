<?php
/**
 * Definition of class PageTemplateTest
 *
 * @copyright  2014-today Justso GmbH
 * @author     j.schirrmacher@justso.de
 * @package    justso\justgen\test
 */

namespace justso\justgen;

use justso\justapi\RequestHelper;
use justso\justapi\testutil\TestEnvironment;

/**
 * Class PageTemplateTest
 * @package justso\justgen\test
 */
class PageTemplateTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Tests the generate() method of the PageTemplate.
     */
    public function testGenerate()
    {
        $env = $this->createTestEnvironment();
        $template = new PageTemplate('testTemplate', ['de']);
        $result = $template->generate('de', 'abc', $env);
        $this->assertSame('prefix Hallo Welt! postfix', $result);
    }

    public function testWithProcessor()
    {
        $env = $this->createTestEnvironment();
        $template = new PageTemplate('testTemplate', ['de']);
        $content = file_get_contents(__DIR__ . '/Processor.php');
        $env->getFileSystem()->putFile('/test-root/processors/testTemplate.php', $content);
        $result = $template->generate('de', 'abc', $env);
        $this->assertSame('Processed', $result);
    }

    public function testGetSmartyVars()
    {
        $env = $this->createTestEnvironment();
        $template = new PageTemplate('testTemplate', ['de']);
        $result = $template->getSmartyVars($env, 'abc');
        $this->assertSame(array('test'), $result);
    }

    /**
     * @return TestEnvironment
     */
    private function createTestEnvironment()
    {
        $env = new TestEnvironment(new RequestHelper());
        $fs = $env->getFileSystem();
        $fs->putFile('/test-root/templates/testTemplate.tpl', 'prefix {$test} postfix');
        $fs->putFile('/test-root/htdocs/nls/abc.js', 'define({"root": {"test": "Hallo Welt!"}});');
        $fs->putFile('/test-root/smartyPlugins/testPlugin.php', '');
        $config = [
            'environments' => ['test' => ['approot' => '/test-root']],
            'languages' => ['de'],
            'pages' => ['abc' => 'testTemplate']
        ];
        $env->getBootstrap()->setTestConfiguration('/test-root', $config);
        return $env;
    }
}
