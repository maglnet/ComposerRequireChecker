<?php

declare(strict_types=1);

namespace ComposerRequireChecker\FileLocator;

use ComposerRequireChecker\Exception\DependenciesNotInstalled;
use ComposerRequireChecker\JsonLoader;
use Generator;
use Psl\File\Exception\NotFoundException;
use Psl\Filesystem;
use Psl\Type;

use function array_key_exists;
use function array_keys;
use function ltrim;
use function str_replace;

/**
 * @psalm-import-type ComposerAutoload from JsonLoader
 * @psalm-import-type InstalledComposerData from JsonLoader
 */
final class LocateComposerPackageDirectDependenciesSourceFiles
{
    public function __construct(private readonly bool $scanFilesFromAutoloadRequires = false)
    {
    }

    /** @return Generator<string> */
    public function __invoke(string $composerJsonPath): Generator
    {
        $path            = Type\non_empty_string()->coerce($composerJsonPath);
        $packageDir      = Filesystem\get_directory($path);
        $composerJson    = JsonLoader::getData($path, JsonLoader::composerDataType());
        $configVendorDir = $composerJson['config']['vendor-dir'] ?? 'vendor';
        $vendorDirs      = [];

        foreach (array_keys($composerJson['require'] ?? []) as $vendorName) {
            $vendorDirs[$vendorName] = $packageDir . '/' . $configVendorDir . '/' . $vendorName;
        }

        $installedPackages = $this->getInstalledPackages($packageDir . '/' . $configVendorDir);

        $locateViaRequires = $this->scanFilesFromAutoloadRequires
            ? new LocateFilesViaRequireStatements()
            : null;

        foreach ($vendorDirs as $vendorName => $vendorDir) {
            if (! array_key_exists($vendorName, $installedPackages)) {
                continue;
            }

            $autoload = $installedPackages[$vendorName];

            yield from (new LocateComposerPackageSourceFiles())->__invoke(['autoload' => $autoload], $vendorDir);

            if ($locateViaRequires !== null) {
                foreach ($autoload['files'] ?? [] as $file) {
                    $filePath = str_replace('\\', '/', $vendorDir . '/' . ltrim($file, '/'));
                    yield from $locateViaRequires($filePath);
                }
            }
        }
    }

    /**
     * Lookup each vendor package's composer.json info from installed.json
     *
     * @return array<string, ComposerAutoload> Keys are the package name and value is the composer.json as an array
     *
     * @throws DependenciesNotInstalled When composer install/update has not been run.
     */
    private function getInstalledPackages(string $vendorDir): array
    {
        try {
            $installedData = JsonLoader::getData(
                $vendorDir . '/composer/installed.json',
                JsonLoader::installedDataType(),
            );
        } catch (NotFoundException) {
            $message = 'The composer dependencies have not been installed, run composer install/update first';

            throw new DependenciesNotInstalled($message);
        }

        $installedPackages = [];

        foreach ($installedData['packages'] ?? [] as $vendorJson) {
            $installedPackages[$vendorJson['name']] = $vendorJson['autoload'] ?? [];
        }

        return $installedPackages;
    }
}
