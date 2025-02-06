<?php

declare(strict_types=1);

namespace ComposerRequireCheckerTest\DefinedSymbolsLocator;

use ComposerRequireChecker\DefinedSymbolsLocator\LocateDefinedSymbolsFromComposerRuntimeApi;
use ComposerRequireChecker\JsonLoader;
use Generator;
use Override;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psl\Json;

class LocateDefinedSymbolsFromComposerRuntimeApiTest extends TestCase
{
    private LocateDefinedSymbolsFromComposerRuntimeApi $locator;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->locator = new LocateDefinedSymbolsFromComposerRuntimeApi();
    }

    /** @dataProvider provideComposerJsonWithUnsuitableComposerRuntimeApi */
    #[DataProvider('provideComposerJsonWithUnsuitableComposerRuntimeApi')]
    public function testNoSymbols(string $composerJson): void
    {
        self::assertEmpty(($this->locator)(Json\typed(
            $composerJson,
            JsonLoader::composerDataType(),
        )));
    }

    /** @dataProvider provideComposerJsonWithSuitableComposerRuntimeApi */
    #[DataProvider('provideComposerJsonWithSuitableComposerRuntimeApi')]
    public function testInstalledVersionsSymbol(string $composerJson): void
    {
        self::assertContains(
            'Composer\InstalledVersions',
            ($this->locator)(Json\typed($composerJson, JsonLoader::composerDataType())),
        );
    }

    /** @return Generator<array-key, array<array-key, string>> */
    public static function provideComposerJsonWithUnsuitableComposerRuntimeApi(): Generator
    {
        yield 'Caret major minor' => ['composerJson' => '{ "require": { "composer-runtime-api": "^1.0" } }'];
        yield 'Caret major' => ['composerJson' => '{ "require": { "composer-runtime-api": "^1" } }'];
        yield 'Tilde major' => ['composerJson' => '{ "require": { "composer-runtime-api": "~1" } }'];
        yield 'Equal major' => ['composerJson' => '{ "require": { "composer-runtime-api": "=1" } }'];
    }

    /** @return Generator<array-key, array<array-key, string>> */
    public static function provideComposerJsonWithSuitableComposerRuntimeApi(): Generator
    {
        yield 'Caret major minor' => ['composerJson' => '{ "require": { "composer-runtime-api": "^2.0" } }'];
        yield 'Caret major' => ['composerJson' => '{ "require": { "composer-runtime-api": "^2" } }'];
        yield 'Tilde major' => ['composerJson' => '{ "require": { "composer-runtime-api": "~2" } }'];
        yield 'Greater equal major' => ['composerJson' => '{ "require": { "composer-runtime-api": ">=2" } }'];
        yield 'Equal major' => ['composerJson' => '{ "require": { "composer-runtime-api": "=2" } }'];
        yield 'Greater major' => ['composerJson' => '{ "require": { "composer-runtime-api": ">2" } }'];
    }
}
