<?php

namespace ComposerRequireCheckerTest\NodeVisitor;

use ComposerRequireChecker\NodeVisitor\DefinedSymbolCollector;
use PhpParser\NodeTraverser;
use PhpParser\NodeTraverserInterface;
use PhpParser\NodeVisitor\NameResolver;
use PhpParser\Parser;
use PhpParser\ParserFactory;
use PHPUnit\Framework\TestCase;

/**
 * @coversNothing
 *
 * @group functional
 */
final class DefinedSymbolCollectorFunctionalTest extends TestCase
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

    public function testWillCollectSymbolsDefinedInThisFile()
    {
        $this->traverseClassAST(self::class);

        self::assertSameCollectedSymbols(
            ['ComposerRequireCheckerTest\NodeVisitor\DefinedSymbolCollectorFunctionalTest'],
            $this->collector->getDefinedSymbols()
        );
    }

    public function testWillCollectFunctionDefinition()
    {
        $this->traverseStringAST('function foo() {}');

        self::assertSameCollectedSymbols(
            ['foo'],
            $this->collector->getDefinedSymbols()
        );
    }

    public function testWillCollectNamespacedFunctionDefinition()
    {
        $this->traverseStringAST('namespace Foo; function foo() {}');

        self::assertSameCollectedSymbols(
            ['Foo\foo'],
            $this->collector->getDefinedSymbols()
        );
    }

    public function testWillCollectConstDefinition()
    {
        $this->traverseStringAST('const foo = "bar", baz = "tab";');

        self::assertSameCollectedSymbols(
            ['foo', 'baz'],
            $this->collector->getDefinedSymbols()
        );
    }

    public function testWillCollectNamespacedConstDefinition()
    {
        $this->traverseStringAST('namespace Foo; const foo = "bar", baz = "tab";');

        self::assertSameCollectedSymbols(
            ['Foo\foo', 'Foo\baz'],
            $this->collector->getDefinedSymbols()
        );
    }

    public function testTraitAdaptionDefinition()
    {
        $this->traverseStringAST('namespace Foo; trait BarTrait { protected function test(){}} class UseTrait { use BarTrait {test as public;} }');

        self::assertSameCollectedSymbols(
            ['Foo\BarTrait', 'Foo\UseTrait'],
            $this->collector->getDefinedSymbols()
        );
    }


    private function traverseStringAST(string $phpSource): array
    {
        return $this->traverser->traverse($this->parser->parse('<?php ' . $phpSource));
    }

    private function traverseClassAST(string $className): array
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
