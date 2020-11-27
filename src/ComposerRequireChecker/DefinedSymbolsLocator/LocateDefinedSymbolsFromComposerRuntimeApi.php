<?php

declare(strict_types=1);

namespace ComposerRequireChecker\DefinedSymbolsLocator;

use function array_key_exists;
use function is_array;
use function is_string;
use function preg_match;

final class LocateDefinedSymbolsFromComposerRuntimeApi
{
    /**
     * @param array<array-key, mixed> $composerData The contents of composer.json for a package
     *
     * @return string[]
     */
    public function __invoke(array $composerData): array
    {
        if (! array_key_exists('require', $composerData)) {
            return [];
        }

        if (! is_array($composerData['require'])) {
            return [];
        }

        $requireSection = $composerData['require'];
        if (! array_key_exists('composer-runtime-api', $requireSection)) {
            return [];
        }

        if (! is_string($requireSection['composer-runtime-api']) || ! preg_match('/^(\^|~|>|>=|=)2/', $requireSection['composer-runtime-api'])) {
            return [];
        }

        return ['Composer\InstalledVersions'];
    }
}
