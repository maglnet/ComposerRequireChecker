<?php

declare(strict_types=1);

namespace ComposerRequireCheckerTest\ASTLocator;

use ArrayObject;
use ComposerRequireChecker\ASTLocator\LocateASTFromFiles;
use ComposerRequireChecker\Exception\FileParseFailed;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use PhpParser\ErrorHandler\Collecting;
use PhpParser\Lexer;
use PhpParser\Node\Stmt;
use PhpParser\Parser\Php7;
use PHPUnit\Framework\TestCase;
use RuntimeException;

use function iterator_to_array;

/** @covers \ComposerRequireChecker\ASTLocator\LocateASTFromFiles */
final class LocateASTFromFilesTest extends TestCase
{
    private LocateASTFromFiles $locator;
    private vfsStreamDirectory $root;

    protected function setUp(): void
    {
        parent::setUp();

        $this->locator = new LocateASTFromFiles(new Php7(new Lexer()), null);
        $this->root    = vfsStream::setup();
    }

    public function testLocate(): void
    {
        $files = [
            $this->createFile('MyClassA', '<?php class MyClassA {}'),
            $this->createFile('MyClassB', '<?php class MyClassB {}'),
            __DIR__ . '/.',
            __DIR__ . '/..',
        ];

        $roots = $this->locate($files);

        $this->assertCount(2, $roots);
    }

    public function testFailOnParseError(): void
    {
        $this->expectException(FileParseFailed::class);
        $this->expectExceptionMessageMatches('/\[vfs:\/\/root\/MyBadCode\]/');
        $files = [
            $this->createFile('MyBadCode', '<?php this causes a parse error'),
        ];

        $this->locate($files);
    }

    public function testDoNotFailOnParseErrorWithErrorHandler(): void
    {
        $collectingErrorHandler = new Collecting();
        $this->locator          = new LocateASTFromFiles(new Php7(new Lexer()), $collectingErrorHandler);
        $files                  = [
            $this->createFile('MyBadCode', '<?php this causes a parse error'),
        ];

        $roots = $this->locate($files);
        $this->assertCount(1, $roots); // one file should be parsed (partially)
        $this->assertTrue($collectingErrorHandler->hasErrors());
        $this->assertCount(1, $collectingErrorHandler->getErrors()); //should have one parse error
    }

    public function testFailOnParseErrorWithNullReturn(): void
    {
        $this->expectException(RuntimeException::class);

        $parserMock = $this->createMock(Php7::class);
        $parserMock->method('parse')->willReturn(null);

        $this->locator = new LocateASTFromFiles($parserMock, null);
        $files         = [
            $this->createFile(
                'MyBadCode',
                'this content is not relevant because the parser is mocked and always returns null',
            ),
        ];

        $this->locate($files);
    }

    private function createFile(string $path, string|null $content = null): string
    {
        return vfsStream::newFile($path)->at($this->root)->setContent($content)->url();
    }

    /**
     * @param string[] $files
     *
     * @return array<array<Stmt>>
     */
    private function locate(array $files): array
    {
        return iterator_to_array(($this->locator)(new ArrayObject($files)));
    }
}
