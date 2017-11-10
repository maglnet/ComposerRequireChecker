<?php declare(strict_types=1);
namespace ComposerRequireChecker\DependencyGuesser;

use ComposerRequireChecker\DefinedSymbolsLocator\LocateDefinedSymbolsFromExtensions;

class GuessFromLoadedExtensions implements GuesserInterface
{

    private $loadedExtensions;

    public function __construct()
    {
        $this->loadedExtensions = get_loaded_extensions();
    }

    public function __invoke(string $symbolName): \Generator
    {
        $definedSymbolsFromExtensions = new LocateDefinedSymbolsFromExtensions();
        foreach ($this->loadedExtensions as $extensionName) {
            $extensionSymbols = $definedSymbolsFromExtensions([$extensionName]);
            if (in_array($symbolName, $extensionSymbols)) {
                yield 'ext-' . $extensionName;
            }
        }
    }
}
