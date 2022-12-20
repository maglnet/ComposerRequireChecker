<?php

declare(strict_types=1);

namespace ComposerRequireCheckerTest\NodeVisitor;

use ComposerRequireChecker\NodeVisitor\UsedSymbolCollector;
use PhpParser\Node\Stmt;
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
final class UsedSymbolCollectorFunctionalTest extends TestCase
{
    private UsedSymbolCollector $collector;

    private Parser $parser;

    private NodeTraverserInterface $traverser;

    protected function setUp(): void
    {
        $this->collector = new UsedSymbolCollector();
        $this->parser    = (new ParserFactory())->create(ParserFactory::PREFER_PHP7);
        $this->traverser = new NodeTraverser();

        $this->traverser->addVisitor(new NameResolver());
        $this->traverser->addVisitor($this->collector);
    }

    public function testWillCollectSymbolsUsedInThisFile(): void
    {
        $this->traverseClassAST(self::class);

        self::assertSameCollectedSymbols(
            [
                'ComposerRequireChecker\NodeVisitor\UsedSymbolCollector',
                'PHPUnit\Framework\TestCase',
                'PhpParser\NodeTraverser',
                'PhpParser\NodeTraverserInterface',
                'PhpParser\Parser',
                'PhpParser\ParserFactory',
                'file_get_contents',
                'ReflectionClass',
                'array_diff',
                'self',
                'PhpParser\NodeVisitor\NameResolver',
                'string',
                'array',
                'void',
            ],
            $this->collector->getCollectedSymbols(),
        );
    }

    public function testWillCollectFunctionDefinitionTypes(): void
    {
        $this->traverseStringAST('<?php function foo(My\ParameterType $bar, array $fooBar) {}');

        self::assertSameCollectedSymbols(
            [
                'My\ParameterType',
                'array',
            ],
            $this->collector->getCollectedSymbols(),
        );
    }

    public function testWillCollectMethodDefinitionTypes(): void
    {
        $this->traverseStringAST('<?php class Foo { function foo(My\ParameterType $bar, array $fooBar) {}}');

        self::assertSameCollectedSymbols(
            [
                'My\ParameterType',
                'array',
            ],
            $this->collector->getCollectedSymbols(),
        );
    }

    public function testWillCollectFunctionReturnTypes(): void
    {
        $this->traverseStringAST('<?php function foo($bar) : My\ReturnType {}');

        self::assertSameCollectedSymbols(
            ['My\ReturnType'],
            $this->collector->getCollectedSymbols(),
        );
    }

    public function testWillCollectMethodReturnTypes(): void
    {
        $this->traverseStringAST('<?php class Foo { function foo($bar) : My\ReturnType {}}');

        self::assertSameCollectedSymbols(
            ['My\ReturnType'],
            $this->collector->getCollectedSymbols(),
        );
    }

    public function testWillCollectSimpleFunctionReturnTypes(): void
    {
        $this->traverseStringAST('<?php function foo($bar) : int {}');

        self::assertSameCollectedSymbols(
            ['int'],
            $this->collector->getCollectedSymbols(),
        );
    }

    public function testWontCollectAnyUsageTypes(): void
    {
        $this->traverseStringAST('<?php function foo($bar) {}');

        self::assertSameCollectedSymbols(
            [],
            $this->collector->getCollectedSymbols(),
        );
    }

    public function testWillCollectPropertyTypes(): void
    {
        $this->traverseStringAST('<?php class Foo { public My\PropertyType $foo; }');

        self::assertSameCollectedSymbols(
            ['My\PropertyType'],
            $this->collector->getCollectedSymbols(),
        );
    }

    public function testUseTraitAdaptionAlias(): void
    {
        $this->traverseStringAST(<<<'PHP'
        <?php
        
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
            ['Foo\BarTrait'],
            $this->collector->getCollectedSymbols(),
        );
    }

    /** @return array<Stmt> */
    private function traverseStringAST(string $stringAST): array
    {
        return $this->traverser->traverse($this->parser->parse($stringAST));
    }

    /** @return array<Stmt> */
    private function traverseClassAST(string $className): array
    {
        return $this->traverseStringAST(file_get_contents(
            (new ReflectionClass($className))
                ->getFileName(),
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
