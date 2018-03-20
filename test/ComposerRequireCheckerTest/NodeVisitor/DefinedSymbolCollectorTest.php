<?php

namespace ComposerRequireCheckerTest\NodeVisitor;

use ComposerRequireChecker\NodeVisitor\DefinedSymbolCollector;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt\Class_;
use PhpParser\NodeTraverser;
use PhpParser\NodeTraverserInterface;
use PhpParser\NodeVisitor\NameResolver;
use PhpParser\Parser;
use PhpParser\ParserFactory;
use PHPUnit\Framework\TestCase;

class DefinedSymbolCollectorTest extends TestCase
{
    /**
     * @var DefinedSymbolCollector
     */
    private $collector;

    /**
     * @var Parser
     */
    private $parser;

    /**
     * @var NodeTraverserInterface
     */
    private $traverser;

    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        $this->collector = new DefinedSymbolCollector();
        $this->parser = (new ParserFactory())->create(ParserFactory::PREFER_PHP7);
        $this->traverser = new NodeTraverser();

        $this->traverser->addVisitor(new NameResolver());
        $this->traverser->addVisitor($this->collector);
    }

    public function testExceptionWhenNoNamespaceDefined()
    {
        $this->expectException(\UnexpectedValueException::class);
        $node = new Class_('gedöns');
        $this->collector->enterNode($node);
    }

    public function testRecordDefinedConstDefinition()
    {
        $node = new FuncCall(new Name('define'), [
            new Arg(new String_('CONST_A')),
            new Arg(new String_('VALUE_A')),
        ]);
        $this->collector->enterNode($node);

        $this->assertContains('CONST_A', $this->collector->getDefinedSymbols());
    }

    public function testDontRecordNamespacedDefinedConstDefinition()
    {
        $node = new FuncCall(new Name('define', ['namespacedName' => new Name\FullyQualified('Foo\define')]), [
            new Arg(new String_('NO_CONST')),
            new Arg(new String_('VALUE_A')),
        ]);
        $this->collector->enterNode($node);

        $this->assertEmpty($this->collector->getDefinedSymbols());
        $this->assertNotContains('NO_CONST', $this->collector->getDefinedSymbols());
    }

}