<?php

use ComposerRequireChecker\NodeVisitor\DefinedSymbolCollector;
use ComposerRequireChecker\NodeVisitor\UsedSymbolCollector;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\NameResolver;
use PhpParser\ParserFactory;

(function () {
    require_once  __DIR__ . '/../vendor/autoload.php';

    $extension = '.php';

    $parser    = (new ParserFactory())->create(ParserFactory::PREFER_PHP7);

    $allFiles = function (Traversable $directories) use ($extension) : Traversable {
        $extensionMatcher = '/.*' . preg_quote($extension) . '$/';

        foreach ($directories as $directory) {
            $allFiles = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($directory),
                \RecursiveIteratorIterator::LEAVES_ONLY
            );

            /* @var $file \SplFileInfo */
            foreach ($allFiles as $file) {
                if (! preg_match($extensionMatcher, $file->getBasename())) {
                    continue;
                }

                yield $file->getPathname();
            }
        }
    };

    $allSourcesASTs = function (Traversable $files) use ($parser) : Traversable {
        foreach ($files as $file) {
            yield $parser->parse(file_get_contents($file));
        }
    };

    $definedSymbolsCollector = new DefinedSymbolCollector();
    $definedSymbolsTraverser = new NodeTraverser();

    $definedSymbolsTraverser->addVisitor(new NameResolver());
    $definedSymbolsTraverser->addVisitor($definedSymbolsCollector);

    $allDefinedSymbols = function (Traversable $ASTs) use ($definedSymbolsTraverser, $definedSymbolsCollector) : array {
        $symbols = [];

        foreach ($ASTs as $astRoot) {
            $definedSymbolsTraverser->traverse($astRoot);

            $symbols = array_merge($symbols, $definedSymbolsCollector->getDefinedSymbols());
        }

        return $symbols;
    };

    $usedSymbolsCollector = new UsedSymbolCollector();
    $usedSymbolsTraverser = new NodeTraverser();

    $usedSymbolsTraverser->addVisitor(new NameResolver());
    $usedSymbolsTraverser->addVisitor($usedSymbolsCollector);


    $allUsedSymbols = function (Traversable $ASTs) use ($usedSymbolsTraverser, $usedSymbolsCollector) : array {
        $symbols = [];

        foreach ($ASTs as $astRoot) {
            $usedSymbolsTraverser->traverse($astRoot);

            $symbols = array_merge($symbols, $usedSymbolsCollector->getCollectedSymbols());
        }

        return $symbols;
    };


    var_dump([
        'defined' => $allDefinedSymbols($allSourcesASTs($allFiles(new ArrayObject([__DIR__ . '/../src', __DIR__ . '/../test'])))),
        'used'    => $allUsedSymbols($allSourcesASTs($allFiles(new ArrayObject([__DIR__ . '/../src', __DIR__ . '/../test'])))),
    ]);
})();
