<?php

declare(strict_types=1);

namespace ComposerRequireChecker\FileLocator;

use ComposerRequireChecker\Exception\DependenciesNotInstalled;
use ComposerRequireChecker\Exception\NotReadable;
use ComposerRequireChecker\JsonLoader;
use Generator;

use function array_key_exists;
use function assert;
use function dirname;
use function is_string;

final class LocateComposerPackageDirectDependenciesSourceFiles
{
    public function __invoke(string $composerJsonPath): Generator
    {
        $packageDir = dirname($composerJsonPath);

        $composerJson    = JsonLoader::getData($composerJsonPath);
        $configVendorDir = $composerJson['config']['vendor-dir'] ?? 'vendor';
        assert(is_string($configVendorDir));
        $vendorDirs = [];

        /**
         * @var mixed $vendorRequiredVersion
         */
        foreach ($composerJson['require'] ?? [] as $vendorName => $vendorRequiredVersion) {
            assert(is_string($vendorName));
            $vendorDirs[$vendorName] = $packageDir . '/' . $configVendorDir . '/' . $vendorName;
        }

        $installedPackages = $this->getInstalledPackages($packageDir . '/' . $configVendorDir);

        foreach ($vendorDirs as $vendorName => $vendorDir) {
            if (! array_key_exists($vendorName, $installedPackages)) {
                continue;
            }

            yield from (new LocateComposerPackageSourceFiles())->__invoke($installedPackages[$vendorName], $vendorDir);
        }
    }

    /**
     * Lookup each vendor package's composer.json info from installed.json
     *
     * @return array<string, array<mixed>> Keys are the package name and value is the composer.json as an array
     *
     * @throws DependenciesNotInstalled When composer install/update has not been run.
     */
    private function getInstalledPackages(string $vendorDir): array
    {
        try {
            $installedData = JsonLoader::getData($vendorDir . '/composer/installed.json');
        } catch (NotReadable $e) {
            $message = 'The composer dependencies have not been installed, run composer install/update first';

            throw new DependenciesNotInstalled($message);
        }

        $installedPackages = [];

        /** @var array<array{name: string}> $packages */
        $packages = $installedData['packages'] ?? $installedData;

        foreach ($packages as $vendorJson) {
            $vendorName                     = $vendorJson['name'];
            $installedPackages[$vendorName] = $vendorJson;
        }

        return $installedPackages;
    }
}
