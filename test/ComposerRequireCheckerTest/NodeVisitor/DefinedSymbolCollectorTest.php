<?php

declare(strict_types=1);

namespace ComposerRequireCheckerTest\NodeVisitor;

use ComposerRequireChecker\NodeVisitor\DefinedSymbolCollector;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\Const_;
use PhpParser\Node\Stmt\Enum_;
use PhpParser\Node\Stmt\Function_;
use PhpParser\Node\Stmt\Interface_;
use PhpParser\Node\Stmt\Trait_;
use PhpParser\NodeTraverser;
use PhpParser\NodeTraverserInterface;
use PhpParser\NodeVisitor\NameResolver;
use PHPUnit\Framework\TestCase;
use UnexpectedValueException;

final class DefinedSymbolCollectorTest extends TestCase
{
    private DefinedSymbolCollector $collector;

    private NodeTraverserInterface $traverser;

    protected function setUp(): void
    {
        $this->collector = new DefinedSymbolCollector();
        $this->traverser = new NodeTraverser();

        $this->traverser->addVisitor(new NameResolver());
        $this->traverser->addVisitor($this->collector);
    }

    public function testExceptionWhenNoNamespaceDefined(): void
    {
        $this->expectException(UnexpectedValueException::class);
        $node = new Class_('gedöns');
        $this->collector->enterNode($node);
    }

    public function testRecordDefinedConstDefinition(): void
    {
        $node = new FuncCall(new Name('define'), [
            new Arg(new String_('CONST_A')),
            new Arg(new String_('VALUE_A')),
        ]);
        $this->collector->enterNode($node);

        $this->assertContains('CONST_A', $this->collector->getDefinedSymbols());
    }

    public function testDontRecordNamespacedDefinedConstDefinition(): void
    {
        $node = new FuncCall(new Name('define', ['namespacedName' => new Name\FullyQualified('Foo\define')]), [
            new Arg(new String_('NO_CONST')),
            new Arg(new String_('VALUE_A')),
        ]);
        $this->collector->enterNode($node);

        $this->assertEmpty($this->collector->getDefinedSymbols());
        $this->assertNotContains('NO_CONST', $this->collector->getDefinedSymbols());
    }

    public function testRecordClassDefinition(): void
    {
        $node = new Class_(new Identifier('Foo'));
        $this->traverser->traverse([$node]);

        $this->assertContains('Foo', $this->collector->getDefinedSymbols());
    }

    public function testRecordEnumDefinition(): void
    {
        $node = new Enum_(new Identifier('Foo'));
        $this->traverser->traverse([$node]);

        $this->assertContains('Foo', $this->collector->getDefinedSymbols());
    }

    public function testRecordInterfaceDefinition(): void
    {
        $node = new Interface_(new Identifier('Foo'));
        $this->traverser->traverse([$node]);

        $this->assertContains('Foo', $this->collector->getDefinedSymbols());
    }

    public function testRecordTraitDefinition(): void
    {
        $node = new Trait_(new Identifier('Foo'));
        $this->traverser->traverse([$node]);

        $this->assertContains('Foo', $this->collector->getDefinedSymbols());
    }

    public function testRecordFunctionDefinition(): void
    {
        $node = new Function_(new Identifier('Foo'));
        $this->traverser->traverse([$node]);

        $this->assertContains('Foo', $this->collector->getDefinedSymbols());
    }

    public function testRecordConstDefinition(): void
    {
        $node = new Const_([
            new \PhpParser\Node\Const_(new Name('CONST_A'), new String_('foo')),
            new \PhpParser\Node\Const_(new Name('CONST_B'), new String_('foo')),
        ]);
        $this->traverser->traverse([$node]);

        $this->assertContains('CONST_A', $this->collector->getDefinedSymbols());
        $this->assertContains('CONST_B', $this->collector->getDefinedSymbols());
    }
}
