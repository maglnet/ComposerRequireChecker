<?php

declare(strict_types=1);

namespace ComposerRequireChecker\DependencyGuesser;

use ComposerRequireChecker\Cli\Options;
use ComposerRequireChecker\DefinedSymbolsLocator\LocateDefinedSymbolsFromExtensions;
use Generator;

use function get_loaded_extensions;
use function in_array;

class GuessFromLoadedExtensions implements Guesser
{
    /** @var array<string> */
    private array $loadedExtensions;

    /** @var string[]|null */
    private array|null $coreExtensions = null;

    public function __construct(Options|null $options = null)
    {
        $this->loadedExtensions = get_loaded_extensions();
        if (! ($options instanceof Options)) {
            return;
        }

        $this->coreExtensions = $options->getPhpCoreExtensions();
    }

    /** @return Generator<string> */
    public function __invoke(string $symbolName): Generator
    {
        $definedSymbolsFromExtensions = new LocateDefinedSymbolsFromExtensions();
        foreach ($this->loadedExtensions as $extensionName) {
            $extensionSymbols = $definedSymbolsFromExtensions([$extensionName]);
            if (! in_array($symbolName, $extensionSymbols)) {
                continue;
            }

            if ($this->coreExtensions && in_array($extensionName, $this->coreExtensions, true)) {
                yield 'php';
            }

            yield 'ext-' . $extensionName;
        }
    }
}
