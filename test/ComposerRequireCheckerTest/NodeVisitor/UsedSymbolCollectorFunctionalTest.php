<?php

declare(strict_types=1);

namespace ComposerRequireCheckerTest\NodeVisitor;

use ComposerRequireChecker\NodeVisitor\UsedSymbolCollector;
use Override;
use PhpParser\NodeTraverser;
use PhpParser\NodeTraverserInterface;
use PhpParser\NodeVisitor\NameResolver;
use PhpParser\Parser;
use PhpParser\ParserFactory;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Psl\File;
use ReflectionClass;

use function array_diff;

#[CoversNothing]
#[Group('functional')]
final class UsedSymbolCollectorFunctionalTest extends TestCase
{
    private UsedSymbolCollector $collector;

    private Parser $parser;

    private NodeTraverserInterface $traverser;

    #[Override]
    protected function setUp(): void
    {
        $this->collector = new UsedSymbolCollector();
        $this->parser    = new ParserFactory()->createForNewestSupportedVersion();
        $this->traverser = new NodeTraverser();

        $this->traverser->addVisitor(new NameResolver());
        $this->traverser->addVisitor($this->collector);
    }

    public function testWillCollectSymbolsUsedInThisFile(): void
    {
        $this->traverseClassAST(self::class);

        self::assertSameCollectedSymbols(
            [
                'Override',
                CoversNothing::class,
                Group::class,
                UsedSymbolCollector::class,
                TestCase::class,
                NodeTraverser::class,
                NodeTraverserInterface::class,
                Parser::class,
                ParserFactory::class,
                'ReflectionClass',
                'array_diff',
                'self',
                NameResolver::class,
                'string',
                'array',
                'void',
                'Psl\File\read',
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

    private function traverseStringAST(string $stringAST): void
    {
        $parsed = $this->parser->parse($stringAST);

        self::assertNotNull($parsed);

        $this->traverser->traverse($parsed);
    }

    /** @param class-string $className */
    private function traverseClassAST(string $className): void
    {
        $fileContent = File\read(new ReflectionClass($className)->getFileName());

        $this->traverseStringAST($fileContent);
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
