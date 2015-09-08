<?php
/**
 * @copyright  2014-today Justso GmbH
 * @author     j.schirrmacher@justso.de
 * @package    justso\justgen\test
 */

class testTemplate implements \justso\justgen\ProcessorInterface
{
    public function __construct($baseUrl)
    {
    }

    public function process($html, $pageName)
    {
        return "Processed";
    }
}
