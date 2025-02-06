<?php

declare(strict_types=1);

namespace ComposerRequireChecker\FileLocator;

use ArrayIterator;
use Generator;
use Psl\Type;

use function array_map;
use function array_merge;
use function array_values;
use function is_dir;
use function is_file;
use function ltrim;
use function str_replace;

/**
 * @psalm-type ComposerConfig = array{vendor-dir?: string}
 * @psalm-type ComposerAutoload = array{
 *                 exclude-from-classmap?: list<string>,
 *                 classmap?: list<string>,
 *                 files?: list<string>,
 *                 psr-0?: array<string, string|list<string>>,
 *                 psr-4?: array<string, string|list<string>>
 *             }
 * @psalm-type ComposerPackageData = array{
 *                 name: string,
 *                 require?: array<string, string>,
 *                 autoload?: ComposerAutoload,
 *                 config?: ComposerConfig
 *             }
 * @psalm-type InstalledComposerPackageData = array{
 *                  name: string,
 *                  require?: array<string, string>,
 *                  autoload?: ComposerAutoload
 *              }
 * @psalm-type ComposerData = array{
 *                 name?: string,
 *                 require?: array<string, string>,
 *                 autoload?: ComposerAutoload,
 *                 config?: ComposerConfig,
 *                 packages?: list<ComposerPackageData>
 *             }
 * @psalm-type InstalledComposerData = array{
 *                 packages?: list<InstalledComposerPackageData>
 *             }
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
                function (string $sourceDir) use ($packageDir): string {
                    return $this->normalizePath($packageDir . '/' . ltrim($sourceDir, '/'));
                },
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
        yield from (new LocateAllFilesByExtension())->__invoke(new ArrayIterator([$directory]), '.php', $blacklist);
    }

    /** @return Type\TypeInterface<InstalledComposerData> */
    public static function installedDataType(): Type\TypeInterface
    {
        $autoload = Type\shape([
            'exclude-from-classmap' => Type\optional(Type\vec(Type\string())),
            'classmap' => Type\optional(Type\vec(Type\string())),
            'files' => Type\optional(Type\vec(Type\string())),
            'psr-0' => Type\optional(Type\dict(
                Type\string(),
                Type\union(Type\string(), Type\vec(Type\string())),
            )),
            'psr-4' => Type\optional(Type\dict(
                Type\string(),
                Type\union(Type\string(), Type\vec(Type\string())),
            )),
        ], true);

        $package = Type\shape([
            'name' => Type\string(),
            'require' => Type\optional(Type\dict(Type\string(), Type\string())),
            'autoload' => Type\optional($autoload),
        ]);

        return Type\shape([
            'packages' => Type\optional(Type\vec($package)),
        ], true);
    }

    /** @return Type\TypeInterface<ComposerData> */
    public static function composerDataType(): Type\TypeInterface
    {
        $composerConfig = Type\shape([
            'vendor-dir' => Type\optional(Type\string()),
        ], true);

        $autoload = Type\shape([
            'exclude-from-classmap' => Type\optional(Type\vec(Type\string())),
            'classmap' => Type\optional(Type\vec(Type\string())),
            'files' => Type\optional(Type\vec(Type\string())),
            'psr-0' => Type\optional(Type\dict(
                Type\string(),
                Type\union(Type\string(), Type\vec(Type\string())),
            )),
            'psr-4' => Type\optional(Type\dict(
                Type\string(),
                Type\union(Type\string(), Type\vec(Type\string())),
            )),
        ], true);

        $package = Type\shape([
            'name' => Type\string(),
            'require' => Type\optional(Type\dict(Type\string(), Type\string())),
            'autoload' => Type\optional($autoload),
            'config' => Type\optional($composerConfig),
        ]);

        return Type\shape([
            'name' => Type\optional(Type\string()),
            'require' => Type\optional(Type\dict(Type\string(), Type\string())),
            'autoload' => Type\optional($autoload),
            'config' => Type\optional($composerConfig),
            'packages' => Type\optional(Type\vec($package)),
        ], true);
    }
}
