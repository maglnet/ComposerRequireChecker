<?php

namespace ComposerRequireChecker\DependencyGuesser;

use ComposerRequireChecker\Cli\Options;
use ComposerRequireChecker\DefinedSymbolsLocator\LocateDefinedSymbolsFromExtensions;

class GuessFromLoadedExtensions implements GuesserInterface
{
    private $loadedExtensions;

    /**
     * @var array|null
     */
    private $coreExtensions;

    public function __construct(?Options $options)
    {
        $this->loadedExtensions = get_loaded_extensions();
        if($options instanceof Options) {
            $this->coreExtensions = $options->getPhpCoreExtensions();
        }
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
