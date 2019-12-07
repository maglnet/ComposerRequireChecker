<?php

namespace ComposerRequireChecker\FileLocator;

use Generator;
use Webmozart\Assert\Assert;

final class LocateComposerPackageSourceFiles
{
    /**
     * @param mixed[] $composerData The contents of composer.json for a package
     * @param string $packageDir The path to package
     * @param string $autoloadKey The key of autoload section from composer.json
     *
     * @return Generator
     */
    public function __invoke(array $composerData, string $packageDir, string $autoloadKey): Generator
    {
        Assert::oneOf($autoloadKey, ['autoload', 'autoload-dev']);

        $autoloadData = $composerData[$autoloadKey] ?? [];

        $blacklist = $autoloadData['exclude-from-classmap'] ?? null;

        yield from $this->locateFilesInClassmapDefinitions(
            $this->getFilePaths($autoloadData['classmap'] ?? [], $packageDir),
            $blacklist
        );
        yield from $this->locateFilesInFilesInFilesDefinitions(
            $this->getFilePaths($autoloadData['files'] ?? [], $packageDir),
            $blacklist
        );
        yield from $this->locateFilesInPsr0Definitions(
            $this->getFilePaths($autoloadData['psr-0'] ?? [], $packageDir),
            $blacklist
        );
        yield from $this->locateFilesInPsr4Definitions(
            $this->getFilePaths($autoloadData['psr-4'] ?? [], $packageDir),
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
