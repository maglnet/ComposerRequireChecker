<?php

namespace ComposerRequireCheckerTest\NodeVisitor;

use ComposerRequireChecker\NodeVisitor\DefinedSymbolCollector;
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

}