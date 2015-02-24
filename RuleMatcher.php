<?php
/**
 * Definition of class RuleMatcher
 * 
 * @copyright  2015-today Justso GmbH
 * @author     j.schirrmacher@justso.de
 * @package    justso\justgen
 */

namespace justso\justgen;

/**
 * Class RuleMatcher
 *
 * @package justso\justgen
 */
class RuleMatcher
{
    private $rules;

    public function __construct(array $rules)
    {
        $this->rules = $rules;
    }

    /**
     * Searches a matching rule for the given name or null if none could be found
     *
     * @param string $what
     * @return string|null
     */
    public function find($what)
    {
        foreach ($this->rules as $name => $entry) {
            $pattern = str_replace(array('/', '*', '%d'), array('\\/', '.*', '\d'), $name);
            if (preg_match('/^' . $pattern . '$/', $what)) {
                return $entry;
            }
        }
        return null;
    }
}
