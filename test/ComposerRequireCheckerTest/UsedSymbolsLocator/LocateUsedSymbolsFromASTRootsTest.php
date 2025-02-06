<?php

declare(strict_types=1);

namespace ComposerRequireCheckerTest\UsedSymbolsLocator;

use ArrayObject;
use ComposerRequireChecker\UsedSymbolsLocator\LocateUsedSymbolsFromASTRoots;
use Override;
use PhpParser\Node;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\Class_;
use PhpParser\ParserFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

use function file_get_contents;

#[CoversClass(LocateUsedSymbolsFromASTRoots::class)]
final class LocateUsedSymbolsFromASTRootsTest extends TestCase
{
    private LocateUsedSymbolsFromASTRoots $locator;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->locator = new LocateUsedSymbolsFromASTRoots();
    }

    public function testNoAsts(): void
    {
        $asts    = [];
        $symbols = $this->locate($asts);

        $this->assertCount(0, $symbols);
    }

    public function testLocate(): void
    {
        $node          = new Class_('Foo');
        $node->extends = new Name('Bar');
        $symbols       = $this->locate([[$node]]);

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

        $parser = $parserFactory->createForNewestSupportedVersion();

        $otherThingContent = file_get_contents(__DIR__ . '/../../fixtures/unknownSymbols/src/OtherThing.php');
        $this->assertNotFalse($otherThingContent);
        $ast1 = $parser->parse($otherThingContent);

        $yetAnotherThingContent = file_get_contents(__DIR__ . '/../../fixtures/unknownSymbols/src/YetAnotherThing.php');
        $this->assertNotFalse($yetAnotherThingContent);
        $ast2 = $parser->parse($yetAnotherThingContent);

        self::assertNotNull($ast1);
        self::assertNotNull($ast2);

        $symbols = $this->locate([$ast1, $ast2]);

        $this->assertSame($expectedSymbols, $symbols);
    }

    /**
     * @param array<array<Node>> $asts
     *
     * @return array<string>
     */
    private function locate(array $asts): array
    {
        return ($this->locator)(new ArrayObject($asts));
    }
}
