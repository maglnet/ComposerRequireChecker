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

        // @todo support "classmap"
        // @todo support "files"
        $sourceDirs = array_merge(
            $composerData['autoload']['psr-0'] ?? [],
            $composerData['autoload']['psr-4'] ?? []
        );

        foreach ($sourceDirs as $sourceDir) {
            yield $packageDir . '/' . ltrim($sourceDir, '/');
        }
    };

    $getPackageDirectDependenciesSourceDirs = function ($composerJsonPath) use ($getPackageSourceDirs) : Traversable {
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

            yield from $getPackageSourceDirs($vendorDir . '/composer.json');
        }
    };

    $allFiles          = new LocateAllFilesByExtension();
    $sourcesASTs       = new LocateASTFromFiles((new ParserFactory())->create(ParserFactory::PREFER_PHP7));
    $allDefinedSymbols = new LocateDefinedSymbolsFromASTRoots();
    $allUsedSymbols    = new LocateUsedSymbolsFromASTRoots();

    $extension    = '.php';
    $composerJson = __DIR__ . '/test-data/zend-feed/composer.json';

    var_dump([
        'defined' => $allDefinedSymbols($sourcesASTs($allFiles($getPackageDirectDependenciesSourceDirs($composerJson), $extension))),
        'used'    => $allUsedSymbols($sourcesASTs($allFiles($getPackageSourceDirs($composerJson), $extension))),
    ]);
})();
