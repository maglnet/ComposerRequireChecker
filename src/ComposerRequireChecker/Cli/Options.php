<?php

namespace ComposerRequireChecker\Cli;


class Options
{

    private $symbolWhitelist = [
        'null', 'true', 'false', // consts
        'static', 'self', 'parent', // class hierarchy
        'array', 'string', 'int', 'float', 'bool' // types
    ];

    private $phpCoreExtensions = ["Core", "standard"];


    public function __construct() {}

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



}