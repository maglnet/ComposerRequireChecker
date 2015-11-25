<?php

use ComposerRequireChecker\ASTLocator\LocateASTFromFiles;
use ComposerRequireChecker\DefinedSymbolsLocator\LocateDefinedSymbolsFromASTRoots;
use ComposerRequireChecker\FileLocator\LocateAllFilesByExtension;
use ComposerRequireChecker\UsedSymbolsLocator\LocateUsedSymbolsFromASTRoots;
use PhpParser\ParserFactory;

(function () {
    require_once  __DIR__ . '/../vendor/autoload.php';

    $allFiles          = new LocateAllFilesByExtension();
    $sourcesASTs       = new LocateASTFromFiles((new ParserFactory())->create(ParserFactory::PREFER_PHP7));
    $allDefinedSymbols = new LocateDefinedSymbolsFromASTRoots();
    $allUsedSymbols    = new LocateUsedSymbolsFromASTRoots();

    $directories = new ArrayObject([__DIR__ . '/../src', __DIR__ . '/../test']);
    $extension   = '.php';

    var_dump([
        'defined' => $allDefinedSymbols($sourcesASTs($allFiles($directories, $extension))),
        'used'    => $allUsedSymbols($sourcesASTs($allFiles($directories, $extension))),
    ]);
})();
