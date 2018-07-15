<?php

namespace ComposerRequireChecker\DefinedSymbolsLocator;

class LocatedSymbolsAndIncludes
{
    /**
     * @var string[]
     */
    private $symbols = [];

    /**
     * @var string[]
     */
    private $includes = [];

    /**
     * @var string[]
     */
    private $previousIncludes = [];

    /**
     * @return string[]
     */
    public function getSymbols(): array
    {
        return $this->symbols;
    }

    /**
     * @return string[]
     */
    public function getIncludes(): array
    {
        return $this->includes;
    }

    /**
     * @param string[] $symbols
     * @return LocatedSymbolsAndIncludes
     */
    public function addSymbols(array $symbols): LocatedSymbolsAndIncludes
    {
        $this->symbols = $this->arrayMergeUnique($this->symbols, $symbols);
        return $this;
    }

    /**
     * @param string[] $includes
     * @return LocatedSymbolsAndIncludes
     */
    public function setIncludes(array $includes): LocatedSymbolsAndIncludes
    {
        $this->includes = array_diff($includes, $this->previousIncludes);
        $this->previousIncludes = $this->arrayMergeUnique($this->previousIncludes, $includes);
        return $this;
    }

    /**
     * @param array $into
     * @param array $add
     * @return array
     */
    private function arrayMergeUnique(array $into, array $add): array
    {
        return array_values(array_unique(array_merge($into, ...$add)));
    }
}