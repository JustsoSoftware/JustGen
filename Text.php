<?php
/**
 * Definition of class Text
 *
 * @copyright  2015-today Justso GmbH
 * @author     j.schirrmacher@justso.de
 * @package    justso\justgen\model
 */

namespace justso\justgen;

/**
 * Class Text
 *
 * @package justso\justgen\model
 */
class Text extends \justso\justtexts\Text
{
    protected function readFileContents($language)
    {
        $texts = parent::readFileContents($language);

        $config = $this->env->getBootstrap()->getConfiguration();
        $ruleMatcher = new RuleMatcher($config['pages']);
        $template = $ruleMatcher->find($this->pageName);
        if ($template != null) {
            $pageTemplate = new PageTemplate(str_replace('dynamic:', '', $template), $this->languages);
            $vars = $pageTemplate->getSmartyVars($this->env, $this->pageName);
            foreach (array_diff($vars, array_keys($texts)) as $missing) {
                $texts[$missing] = array(
                    'id' => $missing,
                    'name' => $missing,
                    'content' => '',
                    'outdated' => false,
                );
            }
        }

        return $texts;
    }
}
