<?php

use ComposerRequireChecker\ASTLocator\LocateASTFromFiles;
use ComposerRequireChecker\FileLocator\LocateAllFilesByExtension;
use ComposerRequireChecker\NodeVisitor\DefinedSymbolCollector;
use ComposerRequireChecker\NodeVisitor\UsedSymbolCollector;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\NameResolver;
use PhpParser\ParserFactory;

(function () {
    require_once  __DIR__ . '/../vendor/autoload.php';

    $allFiles    = new LocateAllFilesByExtension();
    $sourcesASTs = new LocateASTFromFiles((new ParserFactory())->create(ParserFactory::PREFER_PHP7));

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

    $directories = new ArrayObject([__DIR__ . '/../src', __DIR__ . '/../test']);
    $extension   = '.php';

    var_dump([
        'defined' => $allDefinedSymbols($sourcesASTs($allFiles($directories, $extension))),
        'used'    => $allUsedSymbols($sourcesASTs($allFiles($directories, $extension))),
    ]);
})();
