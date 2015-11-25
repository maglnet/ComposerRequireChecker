<?php

use ComposerRequireChecker\ASTLocator\LocateASTFromFiles;
use ComposerRequireChecker\DefinedSymbolsLocator\LocateDefinedSymbolsFromASTRoots;
use ComposerRequireChecker\FileLocator\LocateComposerPackageDirectDependenciesSourceFiles;
use ComposerRequireChecker\FileLocator\LocateComposerPackageSourceFiles;
use ComposerRequireChecker\UsedSymbolsLocator\LocateUsedSymbolsFromASTRoots;
use PhpParser\ParserFactory;

(function () {
    require_once  __DIR__ . '/../vendor/autoload.php';

    $getPackageSourceFiles                  = new LocateComposerPackageSourceFiles();
    $getPackageDirectDependenciesSourceFiles = new LocateComposerPackageDirectDependenciesSourceFiles();

    $sourcesASTs       = new LocateASTFromFiles((new ParserFactory())->create(ParserFactory::PREFER_PHP7));
    $allDefinedSymbols = new LocateDefinedSymbolsFromASTRoots();
    $allUsedSymbols    = new LocateUsedSymbolsFromASTRoots();

    $composerJson = __DIR__ . '/test-data/zend-feed/composer.json';

    var_dump([
        'defined' => $allDefinedSymbols($sourcesASTs(
            (new \ComposerRequireChecker\GeneratorUtil\ComposeGenerators())->__invoke(
                $getPackageSourceFiles($composerJson),
                $getPackageDirectDependenciesSourceFiles($composerJson)
            )
        )),
        'used'    => $allUsedSymbols($sourcesASTs($getPackageSourceFiles($composerJson))),
    ]);
})();
