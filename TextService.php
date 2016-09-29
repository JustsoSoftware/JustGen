<?php
/**
 * Definition of TextService.php
 *
 * @copyright  2016-today Justso GmbH
 * @author     j.schirrmacher@justso.de
 */

namespace justso\justgen;

/**
 * Class TextService
 */
class TextService extends \justso\justtexts\TextService
{
    /**
     * @param string $pageName
     * @return Text
     */
    protected function getTextModel($pageName)
    {
        return $this->environment->getDIC()->get('\justso\justgen\Text', [
            $this->environment,
            $pageName
        ]);
    }
}
