<?php

use ComposerRequireChecker\ASTLocator\LocateASTFromFiles;
use ComposerRequireChecker\DefinedSymbolsLocator\LocateDefinedSymbolsFromASTRoots;
use ComposerRequireChecker\FileLocator\LocateComposerPackageSourceFiles;
use ComposerRequireChecker\UsedSymbolsLocator\LocateUsedSymbolsFromASTRoots;
use PhpParser\ParserFactory;

(function () {
    require_once  __DIR__ . '/../vendor/autoload.php';

    $getPackageSourceFiles = new LocateComposerPackageSourceFiles();

    $getPackageDirectDependenciesSourceDirs = function ($composerJsonPath) use ($getPackageSourceFiles) : Traversable {
        $packageDir = dirname($composerJsonPath);

        $vendorDirs = array_values(array_map(
            function (string $vendorName) use ($packageDir) {
                return $packageDir . '/vendor/' . $vendorName;
            },
            array_keys(json_decode(file_get_contents($composerJsonPath), true)['require'] ?? [])
        ));

        foreach ($vendorDirs as $vendorDir) {
            if (! file_exists($vendorDir . '/composer.json')) {
                continue;
            }

            yield from $getPackageSourceFiles($vendorDir . '/composer.json');
        }
    };

    $sourcesASTs       = new LocateASTFromFiles((new ParserFactory())->create(ParserFactory::PREFER_PHP7));
    $allDefinedSymbols = new LocateDefinedSymbolsFromASTRoots();
    $allUsedSymbols    = new LocateUsedSymbolsFromASTRoots();

    $extension    = '.php';
    $composerJson = __DIR__ . '/test-data/zend-feed/composer.json';

    var_dump([
        'defined' => $allDefinedSymbols($sourcesASTs($getPackageDirectDependenciesSourceDirs($composerJson), $extension)),
        'used'    => $allUsedSymbols($sourcesASTs($getPackageSourceFiles($composerJson), $extension)),
    ]);
})();
