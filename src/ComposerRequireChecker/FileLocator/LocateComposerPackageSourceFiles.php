<?php

declare(strict_types=1);

namespace ComposerRequireChecker\FileLocator;

use ArrayIterator;
use ComposerRequireChecker\JsonLoader;
use Generator;

use function array_map;
use function array_merge;
use function array_values;
use function is_dir;
use function is_file;
use function ltrim;
use function str_replace;

/**
 * @psalm-import-type ComposerAutoload from JsonLoader
 * @psalm-import-type ComposerData from JsonLoader
 */
final class LocateComposerPackageSourceFiles
{
    /**
     * @param array{autoload?: ComposerAutoload} $composerData The contents of composer.json for a package
     * @param string                             $packageDir   The path to package
     * @psalm-param ComposerData $composerData The contents of composer.json for a package
     *
     * @return Generator<string>
     */
    public function __invoke(array $composerData, string $packageDir): Generator
    {
        $blacklist = $composerData['autoload']['exclude-from-classmap'] ?? null;

        yield from $this->locateFilesInClassmapDefinitions(
            $this->getFilePaths($composerData['autoload']['classmap'] ?? [], $packageDir),
            $blacklist,
        );

        yield from $this->locateFilesInFilesInFilesDefinitions(
            $this->getFilePaths($composerData['autoload']['files'] ?? [], $packageDir),
            $blacklist,
        );

        yield from $this->locateFilesInPsr0Definitions(
            $this->getFilePaths(self::flattenPsrPaths($composerData['autoload']['psr-0'] ?? []), $packageDir),
            $blacklist,
        );

        yield from $this->locateFilesInPsr4Definitions(
            $this->getFilePaths(self::flattenPsrPaths($composerData['autoload']['psr-4'] ?? []), $packageDir),
            $blacklist,
        );
    }

    /**
     * @param array<string> $sourceDirs
     *
     * @return list<string>
     */
    private function getFilePaths(array $sourceDirs, string $packageDir): array
    {
        return array_values(
            array_map(
                fn (string $sourceDir): string => $this->normalizePath($packageDir . '/' . ltrim($sourceDir, '/')),
                self::flattenPsrPaths($sourceDirs),
            ),
        );
    }

    private function normalizePath(string $path): string
    {
        // @infection-ignore-all UnwrapStrReplace False positive on Linux; this guards against Windows paths.
        return str_replace('\\', '/', $path);
    }

    /**
     * @param array<string>      $locations
     * @param array<string>|null $blacklist
     *
     * @return Generator<string>
     */
    private function locateFilesInPsr0Definitions(array $locations, array|null $blacklist): Generator
    {
        yield from $this->locateFilesInFilesInFilesDefinitions($locations, $blacklist);
    }

    /**
     * @param array<string>      $locations
     * @param array<string>|null $blacklist
     *
     * @return Generator<string>
     */
    private function locateFilesInPsr4Definitions(array $locations, array|null $blacklist): Generator
    {
        yield from $this->locateFilesInFilesInFilesDefinitions($locations, $blacklist);
    }

    /**
     * @param array<string|list<string>> $paths
     *
     * @return array<string>
     */
    private static function flattenPsrPaths(array $paths): array
    {
        return array_merge(...array_values(array_map(
            /** @param string|list<string> $item */
            static fn (string|array $item): array => (array) $item,
            $paths,
        )));
    }

    /**
     * @param array<string>      $locations
     * @param array<string>|null $blacklist
     *
     * @return Generator<string>
     */
    private function locateFilesInClassmapDefinitions(array $locations, array|null $blacklist): Generator
    {
        yield from $this->locateFilesInFilesInFilesDefinitions($locations, $blacklist);
    }

    /**
     * @param iterable<string>   $locations
     * @param array<string>|null $blacklist
     *
     * @return Generator<string>
     */
    private function locateFilesInFilesInFilesDefinitions(iterable $locations, array|null $blacklist): Generator
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
     *
     * @return Generator<string>
     */
    private function extractFilesFromDirectory(string $directory, array|null $blacklist): Generator
    {
        yield from new LocateAllFilesByExtension()->__invoke(new ArrayIterator([$directory]), '.php', $blacklist);
    }
}
