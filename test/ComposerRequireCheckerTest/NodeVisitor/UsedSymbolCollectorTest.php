<?php

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
use PhpParser\Node\Name;
use PhpParser\Node\Param;
use PhpParser\Node\Stmt\Catch_;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Function_;
use PhpParser\Node\Stmt\Interface_;
use PhpParser\Node\Stmt\TraitUse;
use PhpParser\Node\Stmt\TraitUseAdaptation\Alias;
use PHPUnit\Framework\TestCase;

/**
 * @covers \ComposerRequireChecker\NodeVisitor\UsedSymbolCollector
 */
class UsedSymbolCollectorTest extends TestCase
{
    /** @var UsedSymbolCollector */
    private $visitor;

    protected function setUp()
    {
        parent::setUp();

        $this->visitor = new UsedSymbolCollector();
    }

    public function testExtendingClass()
    {
        $node = new Class_('Foo');
        $node->extends = new Name('Bar');

        $this->visitor->enterNode($node);

        $symbols = $this->visitor->getCollectedSymbols();
        $this->assertCount(1, $symbols);
        $this->assertContains('Bar', $symbols);
    }

    public function testExtendingInterface()
    {
        $node = new Interface_('Foo');
        $node->extends = [new Name('Bar'), new Name('Baz')];

        $this->visitor->enterNode($node);

        $symbols = $this->visitor->getCollectedSymbols();
        $this->assertCount(2, $symbols);
        $this->assertContains('Bar', $symbols);
        $this->assertContains('Baz', $symbols);
    }

    public function testImplements()
    {
        $node = new Class_('Foo');
        $node->implements = [new Name('Bar'), new Name('Baz')];

        $this->visitor->enterNode($node);

        $symbols = $this->visitor->getCollectedSymbols();
        $this->assertCount(2, $symbols);
        $this->assertContains('Bar', $symbols);
        $this->assertContains('Baz', $symbols);
    }

    public function testStaticCall()
    {
        $class = new Name('Foo');
        $node = new StaticCall($class, 'foo');
        $node->class = $class;

        $this->visitor->enterNode($node);

        $symbols = $this->visitor->getCollectedSymbols();
        $this->assertCount(1, $symbols);
        $this->assertContains('Foo', $symbols);
    }

    public function testStaticPropertyFetch()
    {
        $class = new Name('Foo');
        $node = new StaticPropertyFetch($class, 'foo');
        $node->class = $class;

        $this->visitor->enterNode($node);

        $symbols = $this->visitor->getCollectedSymbols();
        $this->assertCount(1, $symbols);
        $this->assertContains('Foo', $symbols);
    }

    public function testClassConstantFetch()
    {
        $class = new Name('Foo');
        $node = new ClassConstFetch($class, 'FOO');
        $node->class = $class;

        $this->visitor->enterNode($node);

        $symbols = $this->visitor->getCollectedSymbols();
        $this->assertCount(1, $symbols);
        $this->assertContains('Foo', $symbols);
    }

    public function testNew()
    {
        $class = new Name('Foo');
        $node = new New_($class);
        $node->class = $class;

        $this->visitor->enterNode($node);

        $symbols = $this->visitor->getCollectedSymbols();
        $this->assertCount(1, $symbols);
        $this->assertContains('Foo', $symbols);
    }

    public function testInstanceof()
    {
        $class = new Name('Foo');
        $node = new Instanceof_(new Variable('foo'), $class);
        $node->class = $class;

        $this->visitor->enterNode($node);

        $symbols = $this->visitor->getCollectedSymbols();
        $this->assertCount(1, $symbols);
        $this->assertContains('Foo', $symbols);
    }

    public function testCatch()
    {
        $class = new Name('Foo');
        $node = new Catch_([$class], new Variable('e'));

        $this->visitor->enterNode($node);

        $symbols = $this->visitor->getCollectedSymbols();
        $this->assertCount(1, $symbols);
        $this->assertContains('Foo', $symbols);
    }

    public function testFunctionCallUsage()
    {
        $functionName = new Name('foo');
        $node = new FuncCall($functionName);
        $node->name = $functionName;

        $this->visitor->enterNode($node);

        $symbols = $this->visitor->getCollectedSymbols();
        $this->assertCount(1, $symbols);
        $this->assertContains('foo', $symbols);
    }

    public function testFunctionParameterType()
    {
        $functionName = new Name('foo');
        $node = new Function_($functionName);
        $node->name = $functionName;
        $param = new Param(new Variable('bar'));
        $param->type = new Name('Baz');
        $node->params = [$param];

        $this->visitor->enterNode($node);

        $symbols = $this->visitor->getCollectedSymbols();
        $this->assertCount(1, $symbols);
        $this->assertContains('Baz', $symbols);
    }

    public function testFunctionParameterTypeAsString()
    {
        $functionName = new Name('foo');
        $node = new Function_($functionName);
        $node->name = $functionName;
        $param = new Param(new Variable('bar'));
        $param->type = 'Baz';
        $node->params = [$param];

        $this->visitor->enterNode($node);

        $symbols = $this->visitor->getCollectedSymbols();
        $this->assertCount(1, $symbols);
        $this->assertContains('Baz', $symbols);
    }

    public function testMethodParameterType()
    {
        $functionName = new Name('foo');
        $node = new ClassMethod($functionName);
        $node->name = $functionName;
        $param = new Param(new Variable('bar'));
        $param->type = new Name('Baz');
        $node->params = [$param];

        $this->visitor->enterNode($node);

        $symbols = $this->visitor->getCollectedSymbols();
        $this->assertCount(1, $symbols);
        $this->assertContains('Baz', $symbols);
    }

    public function testMethodParameterTypeAsString()
    {
        $functionName = new Name('foo');
        $node = new ClassMethod($functionName);
        $node->name = $functionName;
        $param = new Param(new Variable('bar'));
        $param->type = 'Baz';
        $node->params = [$param];

        $this->visitor->enterNode($node);

        $symbols = $this->visitor->getCollectedSymbols();
        $this->assertCount(1, $symbols);
        $this->assertContains('Baz', $symbols);
    }

    public function testFunctionReturnType()
    {
        $functionName = new Name('foo');
        $node = new Function_($functionName);
        $node->returnType = new Name('Bar');

        $this->visitor->enterNode($node);

        $symbols = $this->visitor->getCollectedSymbols();
        $this->assertCount(1, $symbols);
        $this->assertContains('Bar', $symbols);
    }

    public function testFunctionReturnTypeAsString()
    {
        $functionName = new Name('foo');
        $node = new Function_($functionName);
        $node->returnType = 'Bar';

        $this->visitor->enterNode($node);

        $symbols = $this->visitor->getCollectedSymbols();
        $this->assertCount(1, $symbols);
        $this->assertContains('Bar', $symbols);
    }

    public function testMethodReturnType()
    {
        $functionName = new Name('foo');
        $node = new ClassMethod($functionName);
        $node->returnType = new Name('Bar');

        $this->visitor->enterNode($node);

        $symbols = $this->visitor->getCollectedSymbols();
        $this->assertCount(1, $symbols);
        $this->assertContains('Bar', $symbols);
    }

    public function testMethodReturnTypeAsString()
    {
        $functionName = new Name('foo');
        $node = new ClassMethod($functionName);
        $node->returnType = 'Bar';

        $this->visitor->enterNode($node);

        $symbols = $this->visitor->getCollectedSymbols();
        $this->assertCount(1, $symbols);
        $this->assertContains('Bar', $symbols);
    }

    public function testConstantFetch()
    {
        $exceptionClass = new Name('FooException');
        $node = new ConstFetch($exceptionClass);
        $node->name = $exceptionClass;

        $this->visitor->enterNode($node);

        $symbols = $this->visitor->getCollectedSymbols();
        $this->assertCount(1, $symbols);
        $this->assertContains('FooException', $symbols);
    }

    public function testTraits()
    {
        $node = new TraitUse([new Name('Foo')]);

        $this->visitor->enterNode($node);

        $symbols = $this->visitor->getCollectedSymbols();
        $this->assertCount(1, $symbols);
        $this->assertContains('Foo', $symbols);
    }

    public function testTraitUseVisibilityAdaptation()
    {
        $traitUseAdaption = new Alias(null, 'testMethod', Class_::MODIFIER_PUBLIC, null);
        $traitUse = new TraitUse([new Name('Foo')], [$traitUseAdaption]);

        $this->visitor->enterNode($traitUse);

        $symbols = $this->visitor->getCollectedSymbols();
        $this->assertCount(1, $symbols);
        $this->assertContains('Foo', $symbols);
    }

    public function testBeforeTraverseResetsRecordedSymbols()
    {
        $node = new Class_('Foo');
        $node->extends = new Name('Bar');
        $this->visitor->enterNode($node);

        $this->visitor->beforeTraverse([]);

        $this->assertCount(0, $this->visitor->getCollectedSymbols());
    }
}
