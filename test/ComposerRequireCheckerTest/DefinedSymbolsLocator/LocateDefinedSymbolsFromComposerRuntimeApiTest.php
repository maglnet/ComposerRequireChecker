<?php

declare(strict_types=1);

namespace ComposerRequireCheckerTest\DefinedSymbolsLocator;

use ComposerRequireChecker\DefinedSymbolsLocator\LocateDefinedSymbolsFromComposerRuntimeApi;
use Generator;
use PHPUnit\Framework\TestCase;

use function json_decode;

class LocateDefinedSymbolsFromComposerRuntimeApiTest extends TestCase
{
    private LocateDefinedSymbolsFromComposerRuntimeApi $locator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->locator = new LocateDefinedSymbolsFromComposerRuntimeApi();
    }

    /** @dataProvider provideComposerJsonWithUnsuitableComposerRuntimeApi */
    public function testNoSymbols(string $composerJson): void
    {
        $symbols = $this->locate(json_decode($composerJson, true));

        self::assertEmpty($symbols);
    }

    /** @dataProvider provideComposerJsonWithSuitableComposerRuntimeApi */
    public function testInstalledVersionsSymbol(string $composerJson): void
    {
        $symbols = $this->locate(json_decode($composerJson, true));

        self::assertContains('Composer\InstalledVersions', $symbols);
    }

    public function provideComposerJsonWithUnsuitableComposerRuntimeApi(): Generator
    {
        yield ['{ "require": { "composer-runtime-api": "^1.0" } }'];
        yield ['{ "require": { "composer-runtime-api": "^1" } }'];
        yield ['{ "require": { "composer-runtime-api": "~1" } }'];
        yield ['{ "require": { "composer-runtime-api": "=1" } }'];
    }

    public function provideComposerJsonWithSuitableComposerRuntimeApi(): Generator
    {
        yield ['{ "require": { "composer-runtime-api": "^2.0" } }'];
        yield ['{ "require": { "composer-runtime-api": "^2" } }'];
        yield ['{ "require": { "composer-runtime-api": "~2" } }'];
        yield ['{ "require": { "composer-runtime-api": ">=2" } }'];
        yield ['{ "require": { "composer-runtime-api": "=2" } }'];
        yield ['{ "require": { "composer-runtime-api": ">2" } }'];
    }

    /**
     * @param array<string, mixed> $composerData
     *
     * @return string[]
     */
    private function locate(array $composerData): array
    {
        return ($this->locator)($composerData);
    }
}
