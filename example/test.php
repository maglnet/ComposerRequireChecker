<?php

use ComposerRequireChecker\ASTLocator\LocateASTFromFiles;
use ComposerRequireChecker\DefinedSymbolsLocator\LocateDefinedSymbolsFromASTRoots;
use ComposerRequireChecker\FileLocator\LocateAllFilesByExtension;
use ComposerRequireChecker\UsedSymbolsLocator\LocateUsedSymbolsFromASTRoots;
use PhpParser\ParserFactory;

(function () {
    require_once  __DIR__ . '/../vendor/autoload.php';

    $getPackageSourceDirs = function ($composerJsonPath) : Traversable {
        $packageDir   = dirname($composerJsonPath);
        $composerData = json_decode(file_get_contents($composerJsonPath), true);

        return new ArrayObject(array_values(array_map(
            function (string $path) use ($packageDir) {
                return $packageDir . '/' . ltrim($path, '/');
            },
            array_merge(
                $composerData['autoload']['psr-0'] ?? [],
                $composerData['autoload']['psr-4'] ?? []
                // @todo support "classmap"
                // @todo support "files"
            )
        )));
    };

    $allFiles          = new LocateAllFilesByExtension();
    $sourcesASTs       = new LocateASTFromFiles((new ParserFactory())->create(ParserFactory::PREFER_PHP7));
    $allDefinedSymbols = new LocateDefinedSymbolsFromASTRoots();
    $allUsedSymbols    = new LocateUsedSymbolsFromASTRoots();

    $directories = new ArrayObject([__DIR__ . '/../src', __DIR__ . '/../test']);
    $extension   = '.php';

    var_dump([
        'defined' => $allDefinedSymbols($sourcesASTs($allFiles($getPackageSourceDirs(__DIR__ . '/test-data/zend-feed/composer.json'), $extension))),
        'used'    => $allUsedSymbols($sourcesASTs($allFiles($directories, $extension))),
    ]);
})();
