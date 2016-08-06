<?php
/**
 * Created by PhpStorm.
 * User: joe
 * Date: 09.08.15
 * Time: 12:35
 */

namespace justso\justtexts;

use justso\justapi\RequestHelper;
use justso\justgen\model\Page;

class PageTest extends \PHPUnit_Framework_TestCase
{
    public function testPageByIdAndValue()
    {
        $page = new Page('abc', 'def');
        $this->assertSame(array('id' => 'abc', 'name' => 'abc', 'template' => 'def'), $page->getJSON());
    }

    public function testPageByRequest()
    {
        $request = new RequestHelper();
        $request->fillWithData(array('name' => 'test', 'template' => 'abc'));
        $page = new Page(null, null, $request);
        $this->assertSame(array('id' => 'test', 'name' => 'test', 'template' => 'abc'), $page->getJSON());
    }

    public function testAppendConfig()
    {
        $page = new Page('abc', 'def');
        $config = array();
        $page->appendConfig($config);
        $this->assertSame(array('abc' => 'def'), $config);
    }
}
