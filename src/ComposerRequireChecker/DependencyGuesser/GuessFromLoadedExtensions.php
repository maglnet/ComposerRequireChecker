<?php

namespace ComposerRequireChecker\DependencyGuesser;

use ComposerRequireChecker\DefinedSymbolsLocator\LocateDefinedSymbolsFromExtensions;

class GuessFromLoadedExtensions implements GuesserInterface
{
    private $loadedExtensions;

    /**
     * @var array|null
     */
    private $coreExtensions;

    public function __construct(?array $coreExtensions)
    {
        $this->loadedExtensions = get_loaded_extensions();
        $this->coreExtensions = $coreExtensions;
    }

    public function __invoke(string $symbolName): \Generator
    {
        $definedSymbolsFromExtensions = new LocateDefinedSymbolsFromExtensions();
        foreach ($this->loadedExtensions as $extensionName) {
            $extensionSymbols = $definedSymbolsFromExtensions([$extensionName]);
            if (in_array($symbolName, $extensionSymbols)) {
                if ($this->coreExtensions && in_array($extensionName, $this->coreExtensions)) {
                    yield "php";
                    continue;
                }

                yield 'ext-' . $extensionName;
            }
        }
    }
}
