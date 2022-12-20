<?php

declare(strict_types=1);

namespace ComposerRequireCheckerTest\DefinedSymbolsLocator;

use ArrayObject;
use ComposerRequireChecker\DefinedSymbolsLocator\LocateDefinedSymbolsFromASTRoots;
use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\Function_;
use PhpParser\Node\Stmt\Trait_;
use PHPUnit\Framework\TestCase;

/** @covers \ComposerRequireChecker\DefinedSymbolsLocator\LocateDefinedSymbolsFromASTRoots */
final class LocateDefinedSymbolsFromASTRootsTest extends TestCase
{
    private LocateDefinedSymbolsFromASTRoots $locator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->locator = new LocateDefinedSymbolsFromASTRoots();
    }

    public function testNoRoots(): void
    {
        $symbols = $this->locate([]);

        $this->assertCount(0, $symbols);
    }

    public function testBasicLocateClass(): void
    {
        $roots = [
            new Class_('MyClassA'),
            new Class_('MyClassB'),
            new Class_('MyClassC'),
        ];

        $symbols = $this->locate([$roots]);

        $this->assertIsArray($symbols);
        $this->assertCount(3, $symbols);

        $this->assertContains('MyClassA', $symbols);
        $this->assertContains('MyClassB', $symbols);
        $this->assertContains('MyClassC', $symbols);
    }

    public function testBasicLocateFunctions(): void
    {
        $roots = [
            new Function_('myFunctionA'),
            new Class_('myFunctionB'),
        ];

        $symbols = $this->locate([$roots]);

        $this->assertIsArray($symbols);
        $this->assertCount(2, $symbols);

        $this->assertContains('myFunctionA', $symbols);
        $this->assertContains('myFunctionB', $symbols);
    }

    public function testBasicLocateTrait(): void
    {
        $roots = [
            new Trait_('MyTraitA'),
            new Trait_('MyTraitB'),
            new Trait_('MyTraitC'),
        ];

        $symbols = $this->locate([$roots]);

        $this->assertIsArray($symbols);
        $this->assertCount(3, $symbols);

        $this->assertContains('MyTraitA', $symbols);
        $this->assertContains('MyTraitB', $symbols);
        $this->assertContains('MyTraitC', $symbols);
    }

    public function testBasicLocateAnonymous(): void
    {
        $roots = [
            new Class_(null),
        ];

        $symbols = $this->locate([$roots]);

        $this->assertIsArray($symbols);
        $this->assertCount(0, $symbols);
    }

    public function testBasicLocateDefineCalls(): void
    {
        $roots = [
            new FuncCall(new Name('define'), [
                new Arg(new String_('CONST_NAME')),
                new Arg(new String_('CONST_VALUE')),
            ]),
        ];

        $symbols = $this->locate([$roots]);

        $this->assertIsArray($symbols);
        $this->assertCount(1, $symbols);
    }

    public function testBasicDoNotLocateNamespacedDefineCalls(): void
    {
        $roots = [
            new FuncCall(new Name('define', ['namespacedName' => new Name\FullyQualified('Foo\define')]), [
                new Arg(new String_('NO_CONST')),
                new Arg(new String_('NO_SOMETHING')),
            ]),
        ];

        $symbols = $this->locate([$roots]);

        $this->assertIsArray($symbols);
        $this->assertCount(0, $symbols);
    }

    /**
     * @param array<Node> $roots
     *
     * @return array<string>
     */
    private function locate(array $roots): array
    {
        return ($this->locator)(new ArrayObject($roots));
    }
}
