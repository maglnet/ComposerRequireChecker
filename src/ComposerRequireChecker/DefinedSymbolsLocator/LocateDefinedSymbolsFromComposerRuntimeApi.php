<?php

declare(strict_types=1);

namespace ComposerRequireChecker\DefinedSymbolsLocator;

use Composer\InstalledVersions;
use ComposerRequireChecker\JsonLoader;
use Psl\Regex;

/** @psalm-import-type ComposerData from JsonLoader */
final class LocateDefinedSymbolsFromComposerRuntimeApi
{
    /**
     * @param ComposerData $composerData The contents of composer.json for a package
     *
     * @return string[]
     */
    public function __invoke(array $composerData): array
    {
        if (! Regex\matches($composerData['require']['composer-runtime-api'] ?? ' ', '/^(\^|~|>|>=|=)2/')) {
            return [];
        }

        return [InstalledVersions::class];
    }
}
