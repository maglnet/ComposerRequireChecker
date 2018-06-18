<?php

namespace ComposerRequireChecker\Cli;

class Options
{
    private $symbolWhitelist = [
        'null',
        'true',
        'false', // consts
        'static',
        'self',
        'parent', // class hierarchy
        'array',
        'string',
        'int',
        'float',
        'bool',
        'iterable',
        'callable',
        'void',
        'object' // types
    ];

    private $phpCoreExtensions = [
        "Core",
        "date",
        "pcre",
        "Phar",
        "Reflection",
        "SPL",
        "standard",
    ];


    public function __construct(array $actions = [])
    {
        # For backwards compatibility, move any options without an action section to the 'set' section
        foreach ($actions as $key => $option) {
            if (in_array($key, ['set', 'append'], true)) {
                continue;
            }

            if (!array_key_exists('set', $actions)) {
                $actions['set'] = [];
            }
            $actions['set'][$key] = $option;
            unset($actions[$key]);
        }

        foreach ($actions as $action => $options) {
            foreach ($options as $key => $option) {
                $methodName = $action . $this->getCamelCase($key);
                if (!method_exists($this, $methodName)) {
                    throw new \InvalidArgumentException(
                        $key . ' is not a known option - there is no method ' . $methodName
                    );
                }
                $this->$methodName($option);
            }
        }
    }

    /**
     * @return array
     */
    public function getSymbolWhitelist(): array
    {
        return $this->symbolWhitelist;
    }

    /**
     * @return array
     */
    public function getPhpCoreExtensions(): array
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
     * @param array $symbolWhitelist
     */
    public function appendSymbolWhitelist(array $symbolWhitelist)
    {
        $this->symbolWhitelist = array_merge($this->symbolWhitelist, $symbolWhitelist);
    }

    /**
     * @param array $phpCoreExtensions
     */
    public function setPhpCoreExtensions(array $phpCoreExtensions)
    {
        $this->phpCoreExtensions = $phpCoreExtensions;
    }

    /**
     * @param array $phpCoreExtensions
     */
    public function appendPhpCoreExtensions(array $phpCoreExtensions)
    {
        $this->phpCoreExtensions = array_merge($this->phpCoreExtensions, $phpCoreExtensions);
    }

    /**
     * @param string $string some-string
     * @return string someString
     */
    private function getCamelCase(string $string): string
    {
        return ucfirst(str_replace(' ', '', ucwords(str_replace('-', ' ', $string))));
    }
}
