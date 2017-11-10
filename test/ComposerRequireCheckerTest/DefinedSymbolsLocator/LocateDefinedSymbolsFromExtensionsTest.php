<?php declare(strict_types=1);
namespace ComposerRequireCheckerTest\DefinedSymbolsLocator;


use ComposerRequireChecker\DefinedSymbolsLocator\LocateDefinedSymbolsFromExtensions;
use PHPUnit\Framework\TestCase;

class LocateDefinedSymbolsFromExtensionsTest extends TestCase
{

    /**
     * @var LocateDefinedSymbolsFromExtensions
     */
    private $locator;

    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        $this->locator = new LocateDefinedSymbolsFromExtensions();
    }

    public function testThrowsExceptionForUnknownExtension()
    {
        $this->expectException('ComposerRequireChecker\Exception\UnknownExtensionException');
        $this->locator->__invoke(['unknown_extension_name']);
    }

    public function testReturnsFilledArray()
    {
        $symbols = $this->locator->__invoke(['Core']);
        $this->assertGreaterThan(1, count($symbols));
        $this->assertTrue(is_array($symbols));
    }

    public function testSymbolsContainConstants()
    {
        $symbols = $this->locator->__invoke(['Core']);
        $this->assertTrue(in_array('PHP_VERSION', $symbols));
    }

    public function testSymbolsContainFunctions()
    {
        $symbols = $this->locator->__invoke(['Core']);
        $this->assertTrue(in_array('strlen', $symbols));
    }

    public function testSymbolsContainClasses()
    {
        $symbols = $this->locator->__invoke(['Core']);
        $this->assertTrue(in_array('stdClass', $symbols));
    }

    public function testCanHandleMultipleExtensions()
    {
        $coreSymbols = $this->locator->__invoke(['Core']);
        $standardSymbols = $this->locator->__invoke(['standard']);

        $this->assertGreaterThan(1, count($coreSymbols));
        $this->assertGreaterThan(1, count($standardSymbols));

        $combinedSymbols = $this->locator->__invoke(['Core', 'standard']);
        $this->assertSame(array_merge($coreSymbols, $standardSymbols), $combinedSymbols);
    }

}
