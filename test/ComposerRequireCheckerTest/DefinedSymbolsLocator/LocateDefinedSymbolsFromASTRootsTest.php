<?php

namespace ComposerRequireCheckerTest\DefinedSymbolsLocator;

use ArrayObject;
use ComposerRequireChecker\DefinedSymbolsLocator\LocateDefinedSymbolsFromASTRoots;
use PhpParser\Node\Stmt\Class_;
use PHPUnit\Framework\TestCase;

/**
 * @covers \ComposerRequireChecker\DefinedSymbolsLocator\LocateDefinedSymbolsFromASTRoots
 */
class LocateDefinedSymbolsFromASTRootsTest extends TestCase
{
    /** @var LocateDefinedSymbolsFromASTRoots */
    private $locator;

    protected function setUp()
    {
        parent::setUp();

        $this->locator = new LocateDefinedSymbolsFromASTRoots();
    }

    public function testNoRoots()
    {
        $symbols = $this->locate([]);

        $this->assertCount(0, $symbols);
    }

    public function testBasicLocate()
    {
        $roots = [
            [new Class_('MyClassA'), new Class_('MyClassB')],
            [new Class_('MyClassC')],
        ];

        $symbols = $this->locate([$roots]);

        $this->assertContains('MyClassA', $symbols);
        $this->assertContains('MyClassB', $symbols);
        $this->assertContains('MyClassC', $symbols);
    }

    private function locate(array $roots): array
    {
        return ($this->locator)(new ArrayObject($roots));
    }
}
