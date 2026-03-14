<?php

declare(strict_types=1);

namespace ComposerRequireChecker\DefinedSymbolsLocator;

use ArrayIterator;
use ComposerRequireChecker\ASTLocator\LocateASTFromFiles;
use ComposerRequireChecker\FileLocator\LocateComposerPackageSourceFiles;
use ComposerRequireChecker\JsonLoader;
use Psl\Filesystem;
use Psl\Type;

use function array_diff;
use function array_key_exists;
use function array_keys;
use function array_merge;
use function array_unique;
use function array_values;
use function is_array;
use function is_file;
use function str_starts_with;

/**
 * Locates defined symbols from Composer's generated autoload classmap and files,
 * falling back to AST parsing for dependencies not covered by the classmap.
 *
 * @psalm-import-type ComposerAutoload from JsonLoader
 */
final class LocateDefinedSymbolsFromComposerAutoload
{
    /**
     * @return list<string> all defined symbols from direct dependencies
     */
    public function __invoke(
        string $composerJsonPath,
        LocateASTFromFiles $astLocator,
    ): array {
        $path            = Type\non_empty_string()->coerce($composerJsonPath);
        $packageDir      = Filesystem\get_directory($path);
        $composerData    = JsonLoader::getData($path, JsonLoader::composerDataType());
        $configVendorDir = $composerData['config']['vendor-dir'] ?? 'vendor';
        $vendorDir       = $packageDir . '/' . $configVendorDir;
        $directDeps      = array_keys($composerData['require'] ?? []);

        if ($directDeps === []) {
            return [];
        }

        $symbols     = [];
        $coveredDeps = [];

        // Step 1: Extract class-like symbols from Composer's classmap
        $classmapFile = $vendorDir . '/composer/autoload_classmap.php';

        if (is_file($classmapFile)) {
            /** @var mixed $classmap */
            $classmap = require $classmapFile;

            if (is_array($classmap)) {
                foreach ($classmap as $className => $filePath) {
                    $filePathStr = (string) $filePath;

                    foreach ($directDeps as $depName) {
                        if (str_starts_with($filePathStr, $vendorDir . '/' . $depName . '/')) {
                            $symbols[]             = (string) $className;
                            $coveredDeps[$depName] = true;

                            break;
                        }
                    }
                }
            }
        }

        // Step 2: Parse autoload_files entries for functions/constants
        $filesFile = $vendorDir . '/composer/autoload_files.php';

        if (is_file($filesFile)) {
            /** @var mixed $autoloadFiles */
            $autoloadFiles = require $filesFile;

            if (is_array($autoloadFiles)) {
                $depFiles = [];

                foreach ($autoloadFiles as $filePath) {
                    $filePathStr = (string) $filePath;

                    foreach ($directDeps as $depName) {
                        if (str_starts_with($filePathStr, $vendorDir . '/' . $depName . '/')) {
                            $depFiles[]            = $filePathStr;
                            $coveredDeps[$depName] = true;

                            break;
                        }
                    }
                }

                if ($depFiles !== []) {
                    $symbols = array_merge(
                        $symbols,
                        (new LocateDefinedSymbolsFromASTRoots())->__invoke(
                            $astLocator(new ArrayIterator($depFiles)),
                        ),
                    );
                }
            }
        }

        // Step 3: AST fallback for dependencies not covered by classmap
        $uncoveredDeps = array_diff($directDeps, array_keys($coveredDeps));

        if ($uncoveredDeps !== []) {
            $installedPackages     = $this->getInstalledPackages($vendorDir);
            $getPackageSourceFiles = new LocateComposerPackageSourceFiles();

            foreach ($uncoveredDeps as $depName) {
                if (! array_key_exists($depName, $installedPackages)) {
                    continue;
                }

                $symbols = array_merge(
                    $symbols,
                    (new LocateDefinedSymbolsFromASTRoots())->__invoke(
                        $astLocator(
                            $getPackageSourceFiles(
                                ['autoload' => $installedPackages[$depName]],
                                $vendorDir . '/' . $depName,
                            ),
                        ),
                    ),
                );
            }
        }

        return array_values(array_unique($symbols));
    }

    /**
     * @return array<string, ComposerAutoload>
     */
    private function getInstalledPackages(string $vendorDir): array
    {
        $installedJsonPath = $vendorDir . '/composer/installed.json';

        if (! is_file($installedJsonPath)) {
            return [];
        }

        $installedData = JsonLoader::getData(
            Type\non_empty_string()->coerce($installedJsonPath),
            JsonLoader::installedDataType(),
        );

        $installedPackages = [];

        foreach ($installedData['packages'] ?? [] as $vendorJson) {
            $installedPackages[$vendorJson['name']] = $vendorJson['autoload'] ?? [];
        }

        return $installedPackages;
    }
}
