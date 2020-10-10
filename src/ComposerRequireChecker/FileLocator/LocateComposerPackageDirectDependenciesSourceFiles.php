<?php

declare(strict_types=1);

namespace ComposerRequireChecker\FileLocator;

use ComposerRequireChecker\Exception\DependenciesNotInstalledException;
use ComposerRequireChecker\Exception\NotReadableException;
use ComposerRequireChecker\JsonLoader;
use Generator;

use function array_key_exists;
use function dirname;
use function file_get_contents;
use function json_decode;

final class LocateComposerPackageDirectDependenciesSourceFiles
{
    public function __invoke(string $composerJsonPath): Generator
    {
        $packageDir = dirname($composerJsonPath);

        $composerJson    = json_decode(file_get_contents($composerJsonPath), true);
        $configVendorDir = $composerJson['config']['vendor-dir'] ?? 'vendor';
        $vendorDirs      = [];
        foreach ($composerJson['require'] ?? [] as $vendorName => $vendorRequiredVersion) {
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
     * @return array Keys are the package name and value is the composer.json as an array
     *
     * @throws DependenciesNotInstalledException When composer install/update has not been run
     */
    private function getInstalledPackages(string $vendorDir): array
    {
        try {
            $installedData = (new JsonLoader($vendorDir . '/composer/installed.json'))->getData();
        } catch (NotReadableException $e) {
            $message = 'The composer dependencies have not been installed, run composer install/update first';

            throw new DependenciesNotInstalledException($message);
        }

        $installedPackages = [];

        $packages = $installedData['packages'] ?? $installedData;
        foreach ($packages as $vendorJson) {
            $vendorName                     = $vendorJson['name'];
            $installedPackages[$vendorName] = $vendorJson;
        }

        return $installedPackages;
    }
}
