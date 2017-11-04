<?php

namespace ComposerRequireChecker\FileLocator;

use Generator;

final class LocateComposerPackageDirectDependenciesSourceFiles
{
    public function __invoke(string $composerJsonPath): Generator
    {
        $packageDir = dirname($composerJsonPath);

        $vendorDirs = array_values(array_map(
            function (string $vendorName) use ($packageDir) {
                return $packageDir . '/vendor/' . $vendorName;
            },
            array_keys(json_decode(file_get_contents($composerJsonPath), true)['require'] ?? [])
        ));

        foreach ($vendorDirs as $vendorDir) {
            if (!file_exists($vendorDir . '/composer.json')) {
                continue;
            }

            yield from (new LocateComposerPackageSourceFiles())->__invoke($vendorDir . '/composer.json');
        }
    }
}
