<?php

declare(strict_types=1);

namespace ComposerRequireChecker\DefinedSymbolsLocator;

use function array_key_exists;
use function preg_match;

final class LocateDefinedSymbolsFromComposerRuntimeApi
{
    /**
     * @param mixed[] $composerData The contents of composer.json for a package
     *
     * @return string[]
     */
    public function __invoke(array $composerData): array
    {
        $definedSymbols = [];

        if (
            array_key_exists('composer-runtime-api', $composerData['require'] ?? [])
            && preg_match('/^(\^|~|>|>=|=)2/', $composerData['require']['composer-runtime-api'])
        ) {
            $definedSymbols[] = 'Composer\InstalledVersions';
        }

        return $definedSymbols;
    }
}
