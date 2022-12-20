<?php

declare(strict_types=1);

namespace ComposerRequireCheckerTest\NodeVisitor;

use ComposerRequireChecker\NodeVisitor\UsedSymbolCollector;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Expr\ConstFetch;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\Instanceof_;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Expr\StaticPropertyFetch;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Param;
use PhpParser\Node\Stmt\Break_;
use PhpParser\Node\Stmt\Catch_;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Function_;
use PhpParser\Node\Stmt\Interface_;
use PhpParser\Node\Stmt\Property;
use PhpParser\Node\Stmt\TraitUse;
use PhpParser\Node\Stmt\TraitUseAdaptation\Alias;
use PhpParser\Node\Stmt\TraitUseAdaptation\Precedence;
use PHPUnit\Framework\TestCase;

/** @covers \ComposerRequireChecker\NodeVisitor\UsedSymbolCollector */
final class UsedSymbolCollectorTest extends TestCase
{
    private UsedSymbolCollector $visitor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->visitor = new UsedSymbolCollector();
    }

    public function testExtendingClass(): void
    {
        $node          = new Class_('Foo');
        $node->extends = new Name('Bar');

        $this->visitor->enterNode($node);

        $symbols = $this->visitor->getCollectedSymbols();
        $this->assertCount(1, $symbols);
        $this->assertContains('Bar', $symbols);
    }

    public function testExtendingInterface(): void
    {
        $node          = new Interface_('Foo');
        $node->extends = [new Name('Bar'), new Name('Baz')];

        $this->visitor->enterNode($node);

        $symbols = $this->visitor->getCollectedSymbols();
        $this->assertCount(2, $symbols);
        $this->assertContains('Bar', $symbols);
        $this->assertContains('Baz', $symbols);
    }

    public function testImplements(): void
    {
        $node             = new Class_('Foo');
        $node->implements = [new Name('Bar'), new Name('Baz')];

        $this->visitor->enterNode($node);

        $symbols = $this->visitor->getCollectedSymbols();
        $this->assertCount(2, $symbols);
        $this->assertContains('Bar', $symbols);
        $this->assertContains('Baz', $symbols);
    }

    public function testStaticCall(): void
    {
        $class       = new Name('Foo');
        $node        = new StaticCall($class, 'foo');
        $node->class = $class;

        $this->visitor->enterNode($node);

        $symbols = $this->visitor->getCollectedSymbols();
        $this->assertCount(1, $symbols);
        $this->assertContains('Foo', $symbols);
    }

    public function testStaticPropertyFetch(): void
    {
        $class       = new Name('Foo');
        $node        = new StaticPropertyFetch($class, 'foo');
        $node->class = $class;

        $this->visitor->enterNode($node);

        $symbols = $this->visitor->getCollectedSymbols();
        $this->assertCount(1, $symbols);
        $this->assertContains('Foo', $symbols);
    }

    public function testClassConstantFetch(): void
    {
        $class       = new Name('Foo');
        $node        = new ClassConstFetch($class, 'FOO');
        $node->class = $class;

        $this->visitor->enterNode($node);

        $symbols = $this->visitor->getCollectedSymbols();
        $this->assertCount(1, $symbols);
        $this->assertContains('Foo', $symbols);
    }

    public function testNew(): void
    {
        $class       = new Name('Foo');
        $node        = new New_($class);
        $node->class = $class;

        $this->visitor->enterNode($node);

        $symbols = $this->visitor->getCollectedSymbols();
        $this->assertCount(1, $symbols);
        $this->assertContains('Foo', $symbols);
    }

    public function testInstanceof(): void
    {
        $class       = new Name('Foo');
        $node        = new Instanceof_(new Variable('foo'), $class);
        $node->class = $class;

        $this->visitor->enterNode($node);

        $symbols = $this->visitor->getCollectedSymbols();
        $this->assertCount(1, $symbols);
        $this->assertContains('Foo', $symbols);
    }

    public function testCatch(): void
    {
        $class = new Name('Foo');
        $node  = new Catch_([$class], new Variable('e'));

        $this->visitor->enterNode($node);

        $symbols = $this->visitor->getCollectedSymbols();
        $this->assertCount(1, $symbols);
        $this->assertContains('Foo', $symbols);
    }

    public function testFunctionCallUsage(): void
    {
        $functionName = new Name('foo');
        $node         = new FuncCall($functionName);
        $node->name   = $functionName;

        $this->visitor->enterNode($node);

        $symbols = $this->visitor->getCollectedSymbols();
        $this->assertCount(1, $symbols);
        $this->assertContains('foo', $symbols);
    }

    public function testFunctionParameterType(): void
    {
        $functionName       = new Name('foo');
        $node               = new Function_($functionName);
        $node->name         = $functionName;
        $param              = new Param(new Variable('bar'));
        $param->type        = new Name('Baz');
        $anotherParam       = new Param(new Variable('quux'));
        $anotherParam->type = new Identifier('foo');
        $node->params       = [$param, $anotherParam];

        $this->visitor->enterNode($node);

        $symbols = $this->visitor->getCollectedSymbols();
        $this->assertCount(2, $symbols);
        $this->assertContains('Baz', $symbols);
        $this->assertContains('foo', $symbols);
    }

    public function testMethodParameterType(): void
    {
        $functionName = new Name('foo');
        $node         = new ClassMethod($functionName);
        $node->name   = $functionName;
        $param        = new Param(new Variable('bar'));
        $param->type  = new Name('Baz');
        $node->params = [$param];

        $this->visitor->enterNode($node);

        $symbols = $this->visitor->getCollectedSymbols();
        $this->assertCount(1, $symbols);
        $this->assertContains('Baz', $symbols);
    }

    public function testFunctionReturnType(): void
    {
        $functionName     = new Name('foo');
        $node             = new Function_($functionName);
        $node->returnType = new Name('Bar');

        $this->visitor->enterNode($node);

        $symbols = $this->visitor->getCollectedSymbols();
        $this->assertCount(1, $symbols);
        $this->assertContains('Bar', $symbols);
    }

    public function testMethodReturnType(): void
    {
        $functionName     = new Name('foo');
        $node             = new ClassMethod($functionName);
        $node->returnType = new Name('Bar');

        $this->visitor->enterNode($node);

        $symbols = $this->visitor->getCollectedSymbols();
        $this->assertCount(1, $symbols);
        $this->assertContains('Bar', $symbols);
    }

    public function testConstantFetch(): void
    {
        $exceptionClass = new Name('FooException');
        $node           = new ConstFetch($exceptionClass);
        $node->name     = $exceptionClass;

        $this->visitor->enterNode($node);

        $symbols = $this->visitor->getCollectedSymbols();
        $this->assertCount(1, $symbols);
        $this->assertContains('FooException', $symbols);
    }

    public function testTraits(): void
    {
        $node = new TraitUse([new Name('Foo')]);

        $this->visitor->enterNode($node);

        $symbols = $this->visitor->getCollectedSymbols();
        $this->assertCount(1, $symbols);
        $this->assertContains('Foo', $symbols);
    }

    public function testTraitUseVisibilityAdaptation(): void
    {
        $traitUseAdaption = new Alias(null, 'testMethod', Class_::MODIFIER_PUBLIC, null);
        $traitUse         = new TraitUse([new Name('Foo')], [$traitUseAdaption]);

        $this->visitor->enterNode($traitUse);

        $symbols = $this->visitor->getCollectedSymbols();
        $this->assertCount(1, $symbols);
        $this->assertContains('Foo', $symbols);
    }

    public function testTraitUsePrecedenceAdaptation(): void
    {
        $traitUseAdaption = new Precedence(new Name('Bar'), 'testMethod', [new Name('Baz')]);
        $traitUse         = new TraitUse([new Name('Foo')], [$traitUseAdaption]);

        $this->visitor->enterNode($traitUse);

        $symbols = $this->visitor->getCollectedSymbols();
        $this->assertCount(3, $symbols);
        $this->assertContains('Foo', $symbols);
        $this->assertContains('Bar', $symbols);
        $this->assertContains('Baz', $symbols);
    }

    public function testBeforeTraverseResetsRecordedSymbols(): void
    {
        $node          = new Class_('Foo');
        $node->extends = new Name('Bar');
        $this->visitor->enterNode($node);

        $this->visitor->beforeTraverse([]);

        $this->assertCount(0, $this->visitor->getCollectedSymbols());
    }

    public function testPropertyType(): void
    {
        $node       = new Property(Class_::MODIFIER_PUBLIC, []);
        $node->type = new Name('Bar');

        $this->visitor->enterNode($node);

        $symbols = $this->visitor->getCollectedSymbols();
        $this->assertCount(1, $symbols);
        $this->assertContains('Bar', $symbols);
    }

    public function testIgnoresNonNamePropertyType(): void
    {
        $node       = new Property(Class_::MODIFIER_PUBLIC, []);
        $node->type = 'Bar';

        $this->visitor->enterNode($node);

        $symbols = $this->visitor->getCollectedSymbols();
        $this->assertCount(0, $symbols);
    }

    public function testIgnoresUnhandledNodeTypes(): void
    {
        $node = new Break_();

        $this->visitor->enterNode($node);

        $symbols = $this->visitor->getCollectedSymbols();
        $this->assertCount(0, $symbols);
    }
}
