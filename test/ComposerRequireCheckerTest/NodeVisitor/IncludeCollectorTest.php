<?php

namespace ComposerRequireCheckerTest\NodeVisitor;

use ComposerRequireChecker\NodeVisitor\IncludeCollector;
use PhpParser\Node;
use PhpParser\Node\Expr\BinaryOp\Concat;
use PhpParser\Node\Expr\ConstFetch;
use PhpParser\Node\Expr\Include_;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar\MagicConst\Dir;
use PhpParser\Node\Scalar\MagicConst\File;
use PhpParser\Node\Scalar\String_;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;

class IncludeCollectorTest extends TestCase
{
    /**
     * yields testcase datasets
     * @return iterable
     */
    public function provideGetIncluded(): iterable
    {
        yield "no includes" => [__FILE__, [], []];
        yield "simple include" => [__FILE__, ['anywhere/here.php'], [__DIR__ . '/anywhere/here.php']];
        yield "simple absolute include" => [__FILE__, ['/anywhere/here.php'], ['/anywhere/here.php']];
        yield "simple String_ include" => [
            __FILE__,
            [new String_('anywhere/here.php')],
            [__DIR__ . '/anywhere/here.php']
        ];
        yield "absolute include by DIR" => [
            __FILE__,
            [
                new Concat(
                    new Dir(),
                    new String_('anywhere/here.php')
                )
            ],
            [__DIR__ . 'anywhere/here.php']
        ];
        yield "absolute include by FILE" => [
            __FILE__,
            [
                new Concat(
                    new File(),
                    new String_('anywhere/here.php')
                )
            ],
            [__FILE__ . 'anywhere/here.php']
        ];
        yield "includes with variables" => [
            __FILE__,
            [
                new Concat(
                    new ConstFetch(new Name("NAME")),
                    new String_('.php')
                )
            ],
            [
                __DIR__ . DIRECTORY_SEPARATOR . 'DefinedSymbolCollectorFunctionalTest.php',
                __DIR__ . DIRECTORY_SEPARATOR . 'DefinedSymbolCollectorTest.php',
                __DIR__ . DIRECTORY_SEPARATOR . 'UsedSymbolCollectorFunctionalTest.php',
                __DIR__ . DIRECTORY_SEPARATOR . 'UsedSymbolCollectorTest.php'
            ]
        ];
        yield "includes with constants" => [
            __FILE__,
            [
                new Concat(
                    new Variable(new Name('name')),
                    new String_('.php')
                )
            ],
            [
                __DIR__ . DIRECTORY_SEPARATOR . 'DefinedSymbolCollectorFunctionalTest.php',
                __DIR__ . DIRECTORY_SEPARATOR . 'DefinedSymbolCollectorTest.php',
                __DIR__ . DIRECTORY_SEPARATOR . 'UsedSymbolCollectorFunctionalTest.php',
                __DIR__ . DIRECTORY_SEPARATOR . 'UsedSymbolCollectorTest.php'
            ]
        ];
    }

    /**
     * @test
     * @dataProvider provideGetIncluded
     * @param string $file
     * @param array $included
     * @param array $result
     * @return void
     */
    public function testGetIncluded(string $file, array $included, array $result): void
    {
        $collector = new IncludeCollector();
        $reflector = new ReflectionProperty($collector, 'included');
        $reflector->setAccessible(true);
        $reflector->setValue($collector, $included);
        $reflector->setAccessible(false);
        $collected = $collector->getIncluded($file);
        sort($result);
        sort($collected);
        $this->assertEquals($result, $collected);
    }

    /**
     * @test
     * @return void
     */
    public function testBeforeTraverseEmptiesIncludes(): void
    {
        $collector = new IncludeCollector();
        $reflector = new ReflectionProperty($collector, 'included');
        $reflector->setAccessible(true);
        $reflector->setValue($collector, ['a', '#', 'p']);
        $reflector->setAccessible(false);
        $this->assertAttributeCount(3, 'included', $collector);
        $collector->beforeTraverse([]);
        $this->assertAttributeCount(0, 'included', $collector);
    }

    /**
     * @return iterable
     */
    public function provideEnterNode(): iterable
    {
        $expr = new String_('');
        yield "require" => [new Include_($expr, Include_::TYPE_REQUIRE), 1];
        yield "require_once" => [new Include_($expr, Include_::TYPE_REQUIRE_ONCE), 1];
        yield "include" => [new Include_($expr, Include_::TYPE_INCLUDE), 1];
        yield "include_once" => [new Include_($expr, Include_::TYPE_INCLUDE_ONCE), 1];
        yield "different node" => [$expr, 0];
    }

    /**
     * @test
     * @dataProvider provideEnterNode
     * @param Node $node
     * @param int $count
     * @return void
     */
    public function testEnterNode(Node $node, int $count): void
    {
        $collector = new IncludeCollector();
        $collector->enterNode($node);
        $this->assertAttributeCount($count, 'included', $collector);
    }
}
