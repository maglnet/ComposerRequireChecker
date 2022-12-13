<?php

declare(strict_types=1);

namespace ComposerRequireCheckerTest\DefinedSymbolsLocator;

use ComposerRequireChecker\DefinedSymbolsLocator\LocateDefinedSymbolsFromExtensions;
use PHPUnit\Framework\TestCase;

use function array_merge;
use function count;
use function in_array;

use const PHP_VERSION_ID;

final class LocateDefinedSymbolsFromExtensionsTest extends TestCase
{
    private LocateDefinedSymbolsFromExtensions $locator;

    protected function setUp(): void
    {
        $this->locator = new LocateDefinedSymbolsFromExtensions();
    }

    public function testThrowsExceptionForUnknownExtension(): void
    {
        $this->expectException('ComposerRequireChecker\Exception\UnknownExtension');
        $this->locator->__invoke(['unknown_extension_name']);
    }

    public function testReturnsFilledArray(): void
    {
        $symbols = $this->locator->__invoke(['Core']);
        $this->assertGreaterThan(1, count($symbols));
        $this->assertIsArray($symbols);
    }

    public function testSymbolsContainConstants(): void
    {
        $symbols = $this->locator->__invoke(['Core']);
        $this->assertTrue(in_array('PHP_VERSION', $symbols));
    }

    public function testSymbolsContainFunctions(): void
    {
        $symbols = $this->locator->__invoke(['Core']);
        $this->assertTrue(in_array('strlen', $symbols));
    }

    public function testSymbolsContainClasses(): void
    {
        $symbols = $this->locator->__invoke(['Core']);
        $this->assertTrue(in_array('stdClass', $symbols));
    }

    public function testPackageNameInterpolation(): void
    {
        $symbols  = $this->locator->__invoke(['zend-opcache']);
        $symbols2 = $this->locator->__invoke(['Zend Opcache']);
        $this->assertEquals($symbols, $symbols2);
    }

    public function testCanHandleMultipleExtensions(): void
    {
        $coreSymbols     = $this->locator->__invoke(['Core']);
        $standardSymbols = $this->locator->__invoke(['standard']);

        $this->assertGreaterThan(1, count($coreSymbols));
        $this->assertGreaterThan(1, count($standardSymbols));

        $combinedSymbols = $this->locator->__invoke(['Core', 'standard']);
        $this->assertSame(array_merge($coreSymbols, $standardSymbols), $combinedSymbols);
    }

    public function testDoesNotCollectAnySymbolsForTheRandomExtensionOnPhpVersionsLowerThan82(): void
    {
        if (PHP_VERSION_ID >= 80200) {
            $this->markTestSkipped('This test is only relevant for PHP versions lower than 8.2');
        }

        $symbols = $this->locator->__invoke(['random']);

        $this->assertEmpty($symbols);
    }

    public function testCollectsSymbolsForTheRandomExtensionOnPhpVersions82AndHigher(): void
    {
        if (PHP_VERSION_ID < 80200) {
            $this->markTestSkipped('This test is only relevant for PHP versions 8.2 and higher');
        }

        $symbols = $this->locator->__invoke(['random']);

        $this->assertNotEmpty($symbols);
    }

    public function testDoesNotStopCollectingSymbolsWhenSkippingTheRandomExtension(): void
    {
        if (PHP_VERSION_ID >= 80200) {
            $this->markTestSkipped('This test is only relevant for PHP versions lower than 8.2');
        }

        $symbols = $this->locator->__invoke(['random', 'Core']);

        $this->assertNotEmpty($symbols);
    }
}
