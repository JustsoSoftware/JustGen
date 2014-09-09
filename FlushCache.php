<?php
/**
 * Definition of class FlushCache
 *
 * @copyright  2014-today Justso GmbH, Frankfurt, Germany
 * @author     j.schirrmacher@justso.de
 *
 * @package    justso\service
 */

namespace justso\justgen;

use justso\justapi\Bootstrap;
use justso\justapi\RestService;

/**
 * Flushes the page cache
 *
 * @package    justso\service
 */
class FlushCache extends RestService
{
    public function getAction()
    {
        $config = Bootstrap::getInstance()->getConfiguration();
        $website = Bootstrap::getInstance()->getAppRoot() . '/htdocs/';
        $fs = $this->environment->getFileSystem();
        foreach ($config['languages'] as $language) {
            foreach ($fs->glob($website . $language . '/*') as $page) {
                $fs->deleteFile($page);
            }
            $fs->removeDir($website . $language);
        }
        $this->environment->sendJSONResult("ok");
    }
}
