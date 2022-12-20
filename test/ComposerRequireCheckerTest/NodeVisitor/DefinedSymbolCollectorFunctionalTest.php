<?php

declare(strict_types=1);

namespace ComposerRequireCheckerTest\NodeVisitor;

use ComposerRequireChecker\NodeVisitor\DefinedSymbolCollector;
use PhpParser\Node;
use PhpParser\NodeTraverser;
use PhpParser\NodeTraverserInterface;
use PhpParser\NodeVisitor\NameResolver;
use PhpParser\Parser;
use PhpParser\ParserFactory;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

use function array_diff;
use function file_get_contents;

/**
 * @coversNothing
 * @group functional
 */
final class DefinedSymbolCollectorFunctionalTest extends TestCase
{
    private DefinedSymbolCollector $collector;

    private Parser $parser;

    private NodeTraverserInterface $traverser;

    protected function setUp(): void
    {
        $this->collector = new DefinedSymbolCollector();
        $this->parser    = (new ParserFactory())->create(ParserFactory::PREFER_PHP7);
        $this->traverser = new NodeTraverser();

        $this->traverser->addVisitor(new NameResolver());
        $this->traverser->addVisitor($this->collector);
    }

    public function testWillCollectSymbolsDefinedInThisFile(): void
    {
        $this->traverseClassAST(self::class);

        self::assertSameCollectedSymbols(
            ['ComposerRequireCheckerTest\NodeVisitor\DefinedSymbolCollectorFunctionalTest'],
            $this->collector->getDefinedSymbols(),
        );
    }

    public function testWillCollectFunctionDefinition(): void
    {
        $this->traverseStringAST('function foo() {}');

        self::assertSameCollectedSymbols(
            ['foo'],
            $this->collector->getDefinedSymbols(),
        );
    }

    public function testWillCollectNamespacedFunctionDefinition(): void
    {
        $this->traverseStringAST('namespace Foo; function foo() {}');

        self::assertSameCollectedSymbols(
            ['Foo\foo'],
            $this->collector->getDefinedSymbols(),
        );
    }

    public function testWillCollectConstDefinition(): void
    {
        $this->traverseStringAST('const foo = "bar", baz = "tab";');

        self::assertSameCollectedSymbols(
            ['foo', 'baz'],
            $this->collector->getDefinedSymbols(),
        );
    }

    public function testWillCollectNamespacedConstDefinition(): void
    {
        $this->traverseStringAST('namespace Foo; const foo = "bar", baz = "tab";');

        self::assertSameCollectedSymbols(
            ['Foo\foo', 'Foo\baz'],
            $this->collector->getDefinedSymbols(),
        );
    }

    public function testWillCollectDefinedConstDefinition(): void
    {
        $this->traverseStringAST(
            'define("CONST_A", "MY_VALUE"); define("FooSpace\CONST_A", "BAR"); define($foo, "BAR");',
        );

        self::assertSameCollectedSymbols(
            ['CONST_A', 'FooSpace\CONST_A'],
            $this->collector->getDefinedSymbols(),
        );
    }

    public function testWillNotCollectNamespacedDefineCalls(): void
    {
        $this->traverseStringAST(<<<'PHP'
            namespace Foo {
        
            function define($bar, $baz)
            {
                return;
            }
        
            define("NOT_A_CONST", "NOT_SOMETHING");
            }
        PHP);

        self::assertNotContains(
            'NOT_A_CONST',
            $this->collector->getDefinedSymbols(),
        );
    }

    public function testTraitAdaptionDefinition(): void
    {
        $this->traverseStringAST(<<<'PHP'
            namespace Foo;
            
            trait BarTrait
            {
                protected function test()
                {
                }
            }
            
            class UseTrait
            {
                use BarTrait {
                    test as public;
                }
            }
        PHP);

        self::assertSameCollectedSymbols(
            ['Foo\BarTrait', 'Foo\UseTrait'],
            $this->collector->getDefinedSymbols(),
        );
    }

    /** @return array<Node> */
    private function traverseStringAST(string $phpSource): array
    {
        return $this->traverser->traverse($this->parser->parse('<?php ' . $phpSource));
    }

    /** @return array<Node> */
    private function traverseClassAST(string $className): array
    {
        return $this->traverser->traverse($this->parser->parse(
            file_get_contents((new ReflectionClass($className))->getFileName()),
        ));
    }

    /**
     * @param array<mixed> $expected
     * @param array<mixed> $actual
     */
    private static function assertSameCollectedSymbols(array $expected, array $actual): void
    {
        self::assertSame(array_diff($expected, $actual), array_diff($actual, $expected));
    }
}
