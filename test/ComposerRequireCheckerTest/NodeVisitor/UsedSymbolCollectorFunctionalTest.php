<?php

namespace ComposerRequireCheckerTest\NodeVisitor;

use ComposerRequireChecker\NodeVisitor\UsedSymbolCollector;
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

    public function testWillCollectFunctionDefinitionTypes()
    {
        $this->traverseStringAST('<?php function foo(My\ParameterType $bar, array $fooBar) {}');

        self::assertSameCollectedSymbols(
            [
                'My\ParameterType',
                'array',
            ],
            $this->collector->getCollectedSymbols()
        );
    }

    public function testWontCollectAnyUsageTypes()
    {
        $this->traverseStringAST('<?php function foo($bar) {}');

        self::assertSameCollectedSymbols(
            [],
            $this->collector->getCollectedSymbols()
        );
    }

    private function traverseStringAST(string $stringAST)
    {
        return $this->traverser->traverse(
            $this->parser->parse(
                $stringAST
            )
        );
    }

    private function traverseClassAST(string $className) : array
    {
        return $this->traverseStringAST(
                file_get_contents((new \ReflectionClass($className))->getFileName())
        );
    }

    private static function assertSameCollectedSymbols(array $expected, array $actual)
    {
        self::assertSame(array_diff($expected, $actual), array_diff($actual, $expected));
    }
}
