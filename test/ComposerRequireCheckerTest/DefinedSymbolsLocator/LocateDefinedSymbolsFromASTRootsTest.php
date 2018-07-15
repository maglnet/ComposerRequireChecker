<?php

namespace ComposerRequireCheckerTest\DefinedSymbolsLocator;

use ArrayObject;
use ComposerRequireChecker\ASTLocator\FileAST;
use ComposerRequireChecker\DefinedSymbolsLocator\LocateDefinedSymbolsFromASTRoots;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\Function_;
use PhpParser\Node\Stmt\Trait_;
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

        $this->assertCount(2, $symbols);
        $this->assertCount(0, $symbols[0]);
        $this->assertCount(0, $symbols[1]);
    }

    public function testBasicLocateClass()
    {
        $roots = [
            new Class_('MyClassA'), new Class_('MyClassB'),
            new Class_('MyClassC'),
        ];

        $symbols = $this->locate([$roots]);

        $this->assertInternalType('array', $symbols);
        $this->assertCount(2, $symbols);
        $this->assertCount(3, $symbols[0]);
        $this->assertCount(0, $symbols[1]);

        $this->assertContains('MyClassA', $symbols[0]);
        $this->assertContains('MyClassB', $symbols[0]);
        $this->assertContains('MyClassC', $symbols[0]);
    }

    public function testBasicLocateFunctions()
    {
        $roots = [
            new Function_('myFunctionA'),
            new Class_('myFunctionB'),
        ];

        $symbols = $this->locate([$roots]);

        $this->assertInternalType('array', $symbols);
        $this->assertCount(2, $symbols);
        $this->assertCount(2, $symbols[0]);
        $this->assertCount(0, $symbols[1]);

        $this->assertContains('myFunctionA', $symbols[0]);
        $this->assertContains('myFunctionB', $symbols[0]);
    }

    public function testBasicLocateTrait()
    {
        $roots = [
            new Trait_('MyTraitA'), new Trait_('MyTraitB'),
            new Trait_('MyTraitC'),
        ];

        $symbols = $this->locate([$roots]);

        $this->assertInternalType('array', $symbols);
        $this->assertCount(2, $symbols);
        $this->assertCount(3, $symbols[0]);
        $this->assertCount(0, $symbols[1]);

        $this->assertContains('MyTraitA', $symbols[0]);
        $this->assertContains('MyTraitB', $symbols[0]);
        $this->assertContains('MyTraitC', $symbols[0]);
    }

    public function testBasicLocateAnonymous()
    {
        $roots = [
            new Class_(null),
        ];

        $symbols = $this->locate([$roots]);

        $this->assertInternalType('array', $symbols);
        $this->assertCount(2, $symbols);
        $this->assertCount(0, $symbols[0]);
        $this->assertCount(0, $symbols[1]);
    }

    public function testBasicLocateDefineCalls()
    {
        $roots = [
            new FuncCall(new Name('define'), [
                new Arg(new String_('CONST_NAME')),
                new Arg(new String_('CONST_VALUE')),
            ])
        ];

        $symbols = $this->locate([$roots]);

        $this->assertInternalType('array', $symbols);
        $this->assertCount(2, $symbols);
        $this->assertCount(1, $symbols[0]);
        $this->assertCount(0, $symbols[1]);
    }

    public function testBasicDoNotLocateNamespacedDefineCalls()
    {
        $roots = [
            new FuncCall(new Name('define', ['namespacedName' => new Name\FullyQualified('Foo\define')]), [
                new Arg(new String_('NO_CONST')),
                new Arg(new String_('NO_SOMETHING')),
            ])
        ];

        $symbols = $this->locate([$roots]);

        $this->assertInternalType('array', $symbols);
        $this->assertCount(2, $symbols);
        $this->assertCount(0, $symbols[0]);
        $this->assertCount(0, $symbols[1]);
    }

    private function locate(array $roots): array
    {
        foreach ($roots as &$ast) {
            $ast = new FileAST('', $ast);
        }
        return ($this->locator)(new ArrayObject($roots));
    }
}
