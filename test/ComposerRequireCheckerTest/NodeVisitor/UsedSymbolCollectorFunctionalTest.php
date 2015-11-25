<?php

namespace ComposerRequireChecker\NodeVisitor;

use PhpParser\Node;
use PhpParser\NodeTraverser;
use PhpParser\NodeTraverserInterface;
use PhpParser\NodeVisitor\NameResolver;
use PhpParser\Parser;
use PhpParser\ParserFactory;

/**
 * @coversNothing
 *
 * @group functional
 */
final class UsedSymbolCollectorFunctionalTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var UsedSymbolCollector
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
        $this->collector = new UsedSymbolCollector();
        $this->parser    = (new ParserFactory())->create(ParserFactory::PREFER_PHP7);
        $this->traverser = new NodeTraverser();

        $this->traverser->addVisitor(new NameResolver());
        $this->traverser->addVisitor($this->collector);
    }

    public function testWillCollectSymbolsUsedInThisFile()
    {
        $this->traverseClassAST(self::class);

        self::assertSameCollectedSymbols(
            [
                'ComposerRequireChecker\NodeVisitor\UsedSymbolCollector',
                'PHPUnit_Framework_TestCase',
                'PhpParser\NodeTraverser',
                'PhpParser\ParserFactory',
                'file_get_contents',
                'ReflectionClass',
                'array_diff',
                'self',
                'PhpParser\NodeVisitor\NameResolver',
            ],
            $this->collector->getCollectedSymbols()
        );
    }

    private function traverseClassAST(string $className) : array
    {
        return $this->traverser->traverse(
            $this->parser->parse(
                file_get_contents((new \ReflectionClass($className))->getFileName())
            )
        );
    }

    private static function assertSameCollectedSymbols(array $expected, array $actual)
    {
        self::assertSame(array_diff($expected, $actual), array_diff($actual, $expected));
    }
}
