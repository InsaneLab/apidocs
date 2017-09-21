<?php
/**
 * @author Marcel Pociot <m.pociot@gmail.com>
 * @author Tomasz Urban <tomek.urban@insanelab.com>
 * @source https://github.com/mpociot/laravel-apidoc-generator/blob/master/src/Mpociot/ApiDoc/Parsers/RuleDescriptionParser.php
 * @license MIT
 *
 * @category Class
 */

namespace Insanelab\Apidocs\Parsers;

class RuleDescriptionParser
{
    private $rule;

    private $parameters = [];

    const DEFAULT_LOCALE = 'en';

    /**
     * @param null $rule
     */
    public function __construct($rule = null)
    {
        $this->rule = "apidocs::rules.{$rule}";
    }

    /**
     * @return array|string
     */
    public function getDescription()
    {
        return $this->ruleDescriptionExist() ? $this->makeDescription() : [];
    }

    /**
     * @param string|array $parameters
     *
     * @return $this
     */
    public function with($parameters)
    {
        is_array($parameters) ?
            $this->parameters += $parameters :
            $this->parameters[] = $parameters;

        return $this;
    }

    /**
     * @return bool
     */
    protected function ruleDescriptionExist()
    {
        return trans()->hasForLocale($this->rule) || trans()->hasForLocale($this->rule, self::DEFAULT_LOCALE);
    }

    /**
     * @return string
     */
    protected function makeDescription()
    {
        $description = trans()->hasForLocale($this->rule) ?
                            trans()->get($this->rule) :
                            trans()->get($this->rule, [], self::DEFAULT_LOCALE);

        return $this->replaceAttributes($description);
    }

    /**
     * @param string $description$
     *
     * @return string
     */
    protected function replaceAttributes($description)
    {
        foreach ($this->parameters as $parameter) {
            $description = preg_replace('/:attribute/', $parameter, $description, 1);
        }

        return $description;
    }

    /**
     * @param null $rule
     *
     * @return static
     */
    public static function parse($rule = null)
    {
        return new static($rule);
    }
}
