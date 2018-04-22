<?php

namespace ComposerRequireCheckerTest\UsedSymbolsLocator;

use ArrayObject;
use ComposerRequireChecker\ASTLocator\FileAST;
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

        $this->assertCount(2, $symbols);
        $this->assertCount(0, $symbols[0]);
        $this->assertCount(0, $symbols[1]);
    }

    public function testLocate()
    {
        $node = new Class_('Foo');
        $node->extends = new Name('Bar');
        $symbols = $this->locate([[$node]]);

        $this->assertCount(2, $symbols);
        $this->assertCount(1, $symbols[0]);
        $this->assertContains('Bar', $symbols[0]);
        $this->assertCount(0, $symbols[1]);
    }

    /**
     * @return string[]
     */
    private function locate(array $asts): array
    {
        return ($this->locator)(new FileAST('', new ArrayObject($asts)));
    }
}
