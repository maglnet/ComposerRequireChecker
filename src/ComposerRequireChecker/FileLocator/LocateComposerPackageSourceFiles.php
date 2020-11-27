<?php

declare(strict_types=1);

namespace ComposerRequireChecker\FileLocator;

use ArrayIterator;
use Generator;

use function array_map;
use function array_merge;
use function array_reduce;
use function array_values;
use function is_dir;
use function is_file;
use function ltrim;
use function str_replace;

final class LocateComposerPackageSourceFiles
{
    /**
     * @param mixed[] $composerData The contents of composer.json for a package
     * @param string  $packageDir   The path to package
     */
    public function __invoke(array $composerData, string $packageDir): Generator
    {
        /** @var array<string>|null $blacklist */
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

    /**
     * @param array<string> $sourceDirs
     *
     * @return array<string>
     */
    private function getFilePaths(array $sourceDirs, string $packageDir): array
    {
        $flattened = array_reduce(
            $sourceDirs,
            /**
             * @param array|string $sourceDir
             */
            static function (array $sourceDirs, $sourceDir): array {
                return array_merge($sourceDirs, (array) $sourceDir);
            },
            []
        );

        return array_values(
            array_map(
                function (string $sourceDir) use ($packageDir) {
                    return $this->normalizePath($packageDir . '/' . ltrim($sourceDir, '/'));
                },
                $flattened
            )
        );
    }

    private function normalizePath(string $path): string
    {
        return str_replace('\\', '/', $path);
    }

    /**
     * @param array<string>      $locations
     * @param array<string>|null $blacklist
     */
    private function locateFilesInPsr0Definitions(array $locations, ?array $blacklist): Generator
    {
        yield from $this->locateFilesInFilesInFilesDefinitions($locations, $blacklist);
    }

    /**
     * @param array<string>      $locations
     * @param array<string>|null $blacklist
     */
    private function locateFilesInPsr4Definitions(array $locations, ?array $blacklist): Generator
    {
        yield from $this->locateFilesInFilesInFilesDefinitions($locations, $blacklist);
    }

    /**
     * @param array<string>      $locations
     * @param array<string>|null $blacklist
     */
    private function locateFilesInClassmapDefinitions(array $locations, ?array $blacklist): Generator
    {
        yield from $this->locateFilesInFilesInFilesDefinitions($locations, $blacklist);
    }

    /**
     * @param array<string>      $locations
     * @param array<string>|null $blacklist
     */
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

    /**
     * @param array<string>|null $blacklist
     */
    private function extractFilesFromDirectory(string $directory, ?array $blacklist): Generator
    {
        yield from (new LocateAllFilesByExtension())->__invoke(new ArrayIterator([$directory]), '.php', $blacklist);
    }
}
