<?php

namespace ComposerRequireChecker\Cli;

class Options
{

    private $symbolWhitelist = [
        'null', 'true', 'false', // consts
        'static', 'self', 'parent', // class hierarchy
        'array', 'string', 'int', 'float', 'bool', 'iterable', 'callable' // types
    ];

    private $phpCoreExtensions = [
        "Core",
        "date",
        "pcre",
        "Reflection",
        "SPL",
        "standard",
    ];


    public function __construct(array $options = [])
    {
        foreach ($options as $key => $option) {
            $methodName = 'set'.$this->getCamelCase($key);
            if(!method_exists($this, $methodName)) {
                throw new \InvalidArgumentException($key . ' is not a known option - there is no method ' . $methodName);
            }
            $this->$methodName($option);
        }
    }

    /**
     * @return array
     */
    public function getSymbolWhitelist() : array
    {
        return $this->symbolWhitelist;
    }

    /**
     * @return array
     */
    public function getPhpCoreExtensions() : array
    {
        return $this->phpCoreExtensions;
    }

    /**
     * @param array $symbolWhitelist
     */
    public function setSymbolWhitelist(array $symbolWhitelist)
    {
        $this->symbolWhitelist = $symbolWhitelist;
    }

    /**
     * @param array $phpCoreExtensions
     */
    public function setPhpCoreExtensions(array $phpCoreExtensions)
    {
        $this->phpCoreExtensions = $phpCoreExtensions;
    }

    /**
     * @param string $string some-string
     * @return string someString
     */
    private function getCamelCase(string $string) : string
    {
        return ucfirst(str_replace(' ', '', ucwords(str_replace('-', ' ', $string))));
    }
}
