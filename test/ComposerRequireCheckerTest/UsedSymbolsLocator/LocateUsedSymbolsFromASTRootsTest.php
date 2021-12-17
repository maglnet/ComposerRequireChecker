<?php

declare(strict_types=1);

namespace ComposerRequireCheckerTest\UsedSymbolsLocator;

use ArrayObject;
use ComposerRequireChecker\ASTLocator\ASTLoader;
use ComposerRequireChecker\SymbolCache;
use ComposerRequireChecker\UsedSymbolsLocator\LocateUsedSymbolsFromASTRoots;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use PhpParser\ParserFactory;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\NullAdapter;

/**
 * @covers \ComposerRequireChecker\UsedSymbolsLocator\LocateUsedSymbolsFromASTRoots
 */
final class LocateUsedSymbolsFromASTRootsTest extends TestCase
{
    private LocateUsedSymbolsFromASTRoots $locator;

    private vfsStreamDirectory $root;

    protected function setUp(): void
    {
        parent::setUp();

        $this->locator = new LocateUsedSymbolsFromASTRoots(
            new ASTLoader(
                (new ParserFactory())->create(ParserFactory::PREFER_PHP7),
                null
            ),
            new SymbolCache(new NullAdapter())
        );
        $this->root    = vfsStream::setup();
    }

    public function testNoFiles(): void
    {
        $files   = [];
        $symbols = $this->locate($files);

        $this->assertCount(0, $symbols);
    }

    public function testLocate(): void
    {
        vfsStream::create(['Foo.php' => '<?php class Foo extends Bar {}']);

        $files = [
            $this->root->url() . '/does-not-exist.php',
            $this->root->getChild('Foo.php')->url(),
        ];

        $symbols = $this->locate($files);

        $this->assertCount(1, $symbols);
        $this->assertContains('Bar', $symbols);
    }

    public function testInvokeReturnsSymbolsSorted(): void
    {
        $expectedSymbols = [
            'Doctrine\Common\Collections\ArrayCollection',
            'FILTER_VALIDATE_URL',
            'filter_var',
            'Foo\Bar\Baz',
            'libxml_clear_errors',
        ];

        $files   = [__DIR__ . '/../../fixtures/unknownSymbols/src/OtherThing.php'];
        $symbols = $this->locate($files);

        $this->assertSame($expectedSymbols, $symbols);
    }

    /**
     * @param array<string> $files
     *
     * @return array<string>
     */
    private function locate(array $files): array
    {
        return ($this->locator)(new ArrayObject($files));
    }
}
