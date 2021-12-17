<?php

declare(strict_types=1);

namespace ComposerRequireCheckerTest\DefinedSymbolsLocator;

use ArrayObject;
use ComposerRequireChecker\ASTLocator\ASTLoader;
use ComposerRequireChecker\DefinedSymbolsLocator\LocateDefinedSymbolsFromASTRoots;
use ComposerRequireChecker\SymbolCache;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use PhpParser\ParserFactory;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\NullAdapter;

/**
 * @covers \ComposerRequireChecker\DefinedSymbolsLocator\LocateDefinedSymbolsFromASTRoots
 */
final class LocateDefinedSymbolsFromASTRootsTest extends TestCase
{
    private LocateDefinedSymbolsFromASTRoots $locator;

    private vfsStreamDirectory $root;

    protected function setUp(): void
    {
        parent::setUp();

        $this->locator = new LocateDefinedSymbolsFromASTRoots(
            new ASTLoader(
                (new ParserFactory())->create(ParserFactory::PREFER_PHP7),
                null
            ),
            new SymbolCache(new NullAdapter())
        );
        $this->root    = vfsStream::setup();
    }

    public function testNoRoots(): void
    {
        $symbols = $this->locate([]);

        $this->assertCount(0, $symbols);
    }

    public function testBasicLocateClass(): void
    {
        vfsStream::create([
            'MyClassA.php' => '<?php class MyClassA {}',
            'MyClassB.php' => '<?php class MyClassB {}',
            'MyClassC.php' => '<?php class MyClassC {}',
        ]);

        $files = [
            $this->root->url() . '/does-not-exist.php',
            $this->root->getChild('MyClassA.php')->url(),
            $this->root->getChild('MyClassB.php')->url(),
            $this->root->getChild('MyClassC.php')->url(),
        ];

        $symbols = $this->locate($files);

        $this->assertIsArray($symbols);
        $this->assertCount(3, $symbols);

        $this->assertContains('MyClassA', $symbols);
        $this->assertContains('MyClassB', $symbols);
        $this->assertContains('MyClassC', $symbols);
    }

    public function testBasicLocateFunctions(): void
    {
        vfsStream::create([
            'myFunctionA.php' => '<?php function myFunctionA() {}',
            'myFunctionB.php' => '<?php class myFunctionB {}',
        ]);

        $files = [
            $this->root->getChild('myFunctionA.php')->url(),
            $this->root->getChild('myFunctionB.php')->url(),
        ];

        $symbols = $this->locate($files);

        $this->assertIsArray($symbols);
        $this->assertCount(2, $symbols);

        $this->assertContains('myFunctionA', $symbols);
        $this->assertContains('myFunctionB', $symbols);
    }

    public function testBasicLocateTrait(): void
    {
        vfsStream::create([
            'MyTraitA.php' => '<?php trait MyTraitA {}',
            'MyTraitB.php' => '<?php trait MyTraitB {}',
            'MyTraitC.php' => '<?php trait MyTraitC {}',
        ]);

        $files = [
            $this->root->getChild('MyTraitA.php')->url(),
            $this->root->getChild('MyTraitB.php')->url(),
            $this->root->getChild('MyTraitC.php')->url(),
        ];

        $symbols = $this->locate($files);

        $this->assertIsArray($symbols);
        $this->assertCount(3, $symbols);

        $this->assertContains('MyTraitA', $symbols);
        $this->assertContains('MyTraitB', $symbols);
        $this->assertContains('MyTraitC', $symbols);
    }

    public function testBasicLocateAnonymous(): void
    {
        vfsStream::create(['anon.php' => '<?php new class {};']);

        $files = [
            $this->root->getChild('anon.php')->url(),
        ];

        $symbols = $this->locate($files);

        $this->assertIsArray($symbols);
        $this->assertCount(0, $symbols);
    }

    public function testBasicLocateDefineCalls(): void
    {
        vfsStream::create(['define.php' => "<?php define('CONST_NAME', 'CONST_VALUE');"]);

        $files = [
            $this->root->getChild('define.php')->url(),
        ];

        $symbols = $this->locate($files);

        $this->assertIsArray($symbols);
        $this->assertCount(1, $symbols);
    }

    public function testBasicDoNotLocateNamespacedDefineCalls(): void
    {
        vfsStream::create(['define.php' => "<?php \Foo\define('NO_CONST', 'NO_SOMETHING');"]);

        $files = [
            $this->root->getChild('define.php')->url(),
        ];

        $symbols = $this->locate($files);

        $this->assertIsArray($symbols);
        $this->assertCount(0, $symbols);
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
