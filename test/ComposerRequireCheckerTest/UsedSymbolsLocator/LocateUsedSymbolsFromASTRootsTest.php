<?php

namespace ComposerRequireCheckerTest\UsedSymbolsLocator;

use ArrayObject;
use ComposerRequireChecker\UsedSymbolsLocator\LocateUsedSymbolsFromASTRoots;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\Class_;
use PHPUnit\Framework\TestCase;

/**
 * @covers \ComposerRequireChecker\UsedSymbolsLocator\LocateUsedSymbolsFromASTRoots
 */
class LocateUsedSymbolsFromASTRootsTest extends TestCase
{
    /** @var LocateUsedSymbolsFromASTRoots */
    private $locator;

    protected function setUp()
    {
        parent::setUp();

        $this->locator = new LocateUsedSymbolsFromASTRoots();
    }

    public function testNoAsts()
    {
        $asts = [];
        $symbols = $this->locate($asts);

        $this->assertCount(0, $symbols);
    }

    public function testLocate()
    {
        $node = new Class_('Foo');
        $node->extends = new Name('Bar');
        $symbols = $this->locate([[$node]]);

        $this->assertCount(1, $symbols);
        $this->assertContains('Bar', $symbols);
    }

    /**
     * @return string[]
     */
    private function locate(array $asts): array
    {
        return ($this->locator)(new ArrayObject($asts));
    }
}
