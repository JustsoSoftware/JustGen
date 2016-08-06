<?php
/**
 * Definition of class Page
 *
 * @copyright  2014-today Justso GmbH
 * @author     j.schirrmacher@justso.de
 * @package    justso\justgen\model
 */

namespace justso\justgen\model;

use justso\justapi\RequestHelper;

/**
 * JustTexts pages have an additional 'template' attribute, when JustGen is installed as well.
 *
 * @package justso\justgen\model
 */
class Page extends \justso\justtexts\Page
{
    protected $template;

    public function __construct($id = null, $value = null, RequestHelper $request = null)
    {
        if ($request !== null) {
            $this->name = $request->getIdentifierParam('name');
            $this->template = $request->getIdentifierParam('template');
        } else {
            $this->name = $id;
            $this->template = $value;
        }
    }

    public function getJSON()
    {
        $value = parent::getJSON();
        $value['template'] = $this->template;
        return $value;
    }

    public function getId()
    {
        return $this->name;
    }

    public function appendConfig(array &$config)
    {
        $config[$this->name] = $this->template;
    }
}
