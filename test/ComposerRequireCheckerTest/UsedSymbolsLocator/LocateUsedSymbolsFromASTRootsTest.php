<?php

namespace ComposerRequireCheckerTest\UsedSymbolsLocator;

use ArrayObject;
use ComposerRequireChecker\UsedSymbolsLocator\LocateUsedSymbolsFromASTRoots;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\Class_;
use PhpParser\ParserFactory;
use PHPUnit\Framework\TestCase;

/**
 * @covers \ComposerRequireChecker\UsedSymbolsLocator\LocateUsedSymbolsFromASTRoots
 */
class LocateUsedSymbolsFromASTRootsTest extends TestCase
{
    /** @var LocateUsedSymbolsFromASTRoots */
    private $locator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->locator = new LocateUsedSymbolsFromASTRoots();
    }

    public function testNoAsts(): void
    {
        $asts = [];
        $symbols = $this->locate($asts);

        $this->assertCount(0, $symbols);
    }

    public function testLocate(): void
    {
        $node = new Class_('Foo');
        $node->extends = new Name('Bar');
        $symbols = $this->locate([[$node]]);

        $this->assertCount(1, $symbols);
        $this->assertContains('Bar', $symbols);
    }

    public function testInvokeReturnsSymbolsSorted(): void
    {
        $expectedSymbols = [
            'Doctrine\Common\Collections\ArrayCollection',
            'FILTER_VALIDATE_URL',
            'filter_var',
            'Foo\Bar\Baz',
            'libxml_clear_errors',
        ];

        $parserFactory = new ParserFactory();

        $parser = $parserFactory->create(ParserFactory::PREFER_PHP7);

        $ast = $parser->parse(file_get_contents(__DIR__ . '/../../fixtures/unknownSymbols/src/OtherThing.php'));

        $symbols = $this->locate([
            $ast,
        ]);

        $this->assertSame($expectedSymbols, $symbols);
    }

    /**
     * @return string[]
     */
    private function locate(array $asts): array
    {
        return ($this->locator)(new ArrayObject($asts));
    }
}
