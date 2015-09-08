<?php
/**
 * Definition of interface ProcessorInterface
 *
 * @copyright  2015-today Justso GmbH
 * @author     j.schirrmacher@justso.de
 * @package    justso\justgen
 */

namespace justso\justgen;

/**
 * Interface for classes which process generated page content
 */
interface ProcessorInterface
{
    /**
     * Process given content.
     *
     * @param string $content
     * @param string $pageName
     * @return string Processed content
     */
    public function process($content, $pageName);
}
