<?php

use ComposerRequireChecker\ASTLocator\LocateASTFromFiles;
use ComposerRequireChecker\DefinedSymbolsLocator\LocateDefinedSymbolsFromASTRoots;
use ComposerRequireChecker\FileLocator\LocateComposerPackageDirectDependenciesSourceFiles;
use ComposerRequireChecker\FileLocator\LocateComposerPackageSourceFiles;
use ComposerRequireChecker\GeneratorUtil\ComposeGenerators;
use ComposerRequireChecker\UsedSymbolsLocator\LocateUsedSymbolsFromASTRoots;
use PhpParser\ParserFactory;

// Please run "run-test.sh", and not this file directly.

(function () {
    require_once  __DIR__ . '/../vendor/autoload.php';

    $getPackageSourceFiles = new LocateComposerPackageSourceFiles();

    $sourcesASTs  = new LocateASTFromFiles((new ParserFactory())->create(ParserFactory::PREFER_PHP7));
    $composerJson = __DIR__ . '/test-data/zend-feed/composer.json';

    $definedSymbols = (new LocateDefinedSymbolsFromASTRoots())->__invoke($sourcesASTs(
        (new ComposeGenerators())->__invoke(
            $getPackageSourceFiles($composerJson),
            (new LocateComposerPackageDirectDependenciesSourceFiles())->__invoke($composerJson)
        )
    ));
    $usedSymbols = (new LocateUsedSymbolsFromASTRoots())->__invoke($sourcesASTs($getPackageSourceFiles($composerJson)));
    $unknownSymbols = array_diff($usedSymbols, $definedSymbols);

    var_dump(['unknown_symbols' => $unknownSymbols]);

    exit((int) (bool) $unknownSymbols);
})();
