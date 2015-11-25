<?php

namespace ComposerRequireChecker\FileLocator;

use Generator;

final class LocateComposerPackageSourceFiles
{
    public function __invoke(string $composerJsonPath) : Generator
    {
        $packageDir   = dirname($composerJsonPath);
        $composerData = json_decode(file_get_contents($composerJsonPath), true);

        yield from $this->locateFilesInClassmapDefinitions(
            $this->getFilePaths($composerData['autoload']['classmap'] ?? [], $packageDir)
        );
        yield from $this->locateFilesInFilesInFilesDefinitions(
            $this->getFilePaths($composerData['autoload']['files'] ?? [], $packageDir)
        );
        yield from $this->locateFilesInPsr0Definitions(
            $this->getFilePaths($composerData['autoload']['psr-0'] ?? [], $packageDir)
        );
        yield from $this->locateFilesInPsr4Definitions(
            $this->getFilePaths($composerData['autoload']['psr-4'] ?? [], $packageDir)
        );
    }

    private function getFilePaths(array $sourceDirs, string $packageDir) : array
    {
        return array_values(array_map(
            function (string $sourceDir) use ($packageDir) {
                return $packageDir . '/' . ltrim($sourceDir, '/');
            },
            $sourceDirs
        ));
    }

    private function locateFilesInPsr0Definitions(array $locations) : Generator
    {
        yield from $this->locateFilesInFilesInFilesDefinitions($locations);
    }

    private function locateFilesInPsr4Definitions(array $locations) : Generator
    {
        yield from $this->locateFilesInFilesInFilesDefinitions($locations);
    }

    private function locateFilesInClassmapDefinitions(array $locations) : Generator
    {
        yield from $this->locateFilesInFilesInFilesDefinitions($locations);
    }

    private function locateFilesInFilesInFilesDefinitions(array $locations) : Generator
    {
        foreach ($locations as $location) {
            if (is_file($location)) {
                yield $location;
            }

            yield from $this->extractFilesFromDirectory($location);
        }
    }

    private function extractFilesFromDirectory(string $directory) : Generator
    {
        yield from (new LocateAllFilesByExtension())->__invoke(new \ArrayIterator([$directory]), '.php');
    }
}
