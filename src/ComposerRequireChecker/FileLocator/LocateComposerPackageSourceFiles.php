<?php

namespace ComposerRequireChecker\FileLocator;

use Generator;

final class LocateComposerPackageSourceFiles
{
    public function __invoke(string $composerJsonPath): Generator
    {
        $packageDir = dirname($composerJsonPath);
        $composerData = json_decode(file_get_contents($composerJsonPath), true);

        $blacklist = $composerData['autoload']['exclude-from-classmap'] ?? null;

        yield from $this->locateFilesInClassmapDefinitions(
            $this->getFilePaths($composerData['autoload']['classmap'] ?? [], $packageDir),
            $blacklist
        );
        yield from $this->locateFilesInFilesInFilesDefinitions(
            $this->getFilePaths($composerData['autoload']['files'] ?? [], $packageDir),
            $blacklist
        );
        yield from $this->locateFilesInPsr0Definitions(
            $this->getFilePaths($composerData['autoload']['psr-0'] ?? [], $packageDir),
            $blacklist
        );
        yield from $this->locateFilesInPsr4Definitions(
            $this->getFilePaths($composerData['autoload']['psr-4'] ?? [], $packageDir),
            $blacklist
        );
    }

    private function getFilePaths(array $sourceDirs, string $packageDir): array
    {
        $flattened = array_reduce(
            $sourceDirs,
            function (array $sourceDirs, $sourceDir) {
                return array_merge($sourceDirs, (array)$sourceDir);
            },
            []
        );
        return array_values(array_map(
            function (string $sourceDir) use ($packageDir) {
                return $this->normalizePath($packageDir . '/' . ltrim($sourceDir, '/'));
            },
            $flattened
        ));
    }

    private function normalizePath(string $path): string
    {
        return str_replace('\\', '/', $path);
    }

    private function locateFilesInPsr0Definitions(array $locations, ?array $blacklist): Generator
    {
        yield from $this->locateFilesInFilesInFilesDefinitions($locations, $blacklist);
    }

    private function locateFilesInPsr4Definitions(array $locations, ?array $blacklist): Generator
    {
        yield from $this->locateFilesInFilesInFilesDefinitions($locations, $blacklist);
    }

    private function locateFilesInClassmapDefinitions(array $locations, ?array $blacklist): Generator
    {
        yield from $this->locateFilesInFilesInFilesDefinitions($locations, $blacklist);
    }

    private function locateFilesInFilesInFilesDefinitions(array $locations, ?array $blacklist): Generator
    {
        foreach ($locations as $location) {
            if (is_file($location)) {
                yield $location;
            } elseif (is_dir($location)) {
                yield from $this->extractFilesFromDirectory($location, $blacklist);
            }
        }
    }

    private function extractFilesFromDirectory(string $directory, ?array $blacklist): Generator
    {
        yield from (new LocateAllFilesByExtension())->__invoke(new \ArrayIterator([$directory]), '.php', $blacklist);
    }
}
