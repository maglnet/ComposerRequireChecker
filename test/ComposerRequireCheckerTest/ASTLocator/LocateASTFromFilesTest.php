<?php

declare(strict_types=1);

namespace ComposerRequireCheckerTest\ASTLocator;

use ArrayObject;
use ComposerRequireChecker\ASTLocator\LocateASTFromFiles;
use ComposerRequireChecker\Exception\FileParseFailed;
use PhpParser\ErrorHandler\Collecting;
use PhpParser\Lexer;
use PhpParser\Node\Stmt;
use PhpParser\Parser\Php7;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Spatie\TemporaryDirectory\TemporaryDirectory;

use function file_put_contents;
use function iterator_to_array;

/** @covers \ComposerRequireChecker\ASTLocator\LocateASTFromFiles */
final class LocateASTFromFilesTest extends TestCase
{
    private LocateASTFromFiles $locator;
    private TemporaryDirectory $root;

    protected function setUp(): void
    {
        parent::setUp();

        $this->locator = new LocateASTFromFiles(new Php7(new Lexer()), null);
        $this->root    = (new TemporaryDirectory())
            ->deleteWhenDestroyed()
            ->create();
    }

    public function testLocate(): void
    {
        $files = [
            __DIR__ . '/.',
            __DIR__ . '/..',
            $this->createFile('MyClassA.php', '<?php class MyClassA {}'),
            $this->createFile('MyClassB.php', '<?php class MyClassB {}'),
        ];

        $roots = $this->locate($files);

        $this->assertCount(2, $roots);
    }

    public function testFailOnParseError(): void
    {
        $files = [
            $this->createFile('MyBadCode.php', '<?php this causes a parse error'),
        ];

        $filePath = $files[0];

        $this->expectException(FileParseFailed::class);
        $this->expectExceptionMessage('[' . $filePath . ']');

        $this->locate($files);
    }

    public function testDoNotFailOnParseErrorWithErrorHandler(): void
    {
        $collectingErrorHandler = new Collecting();
        $this->locator          = new LocateASTFromFiles(new Php7(new Lexer()), $collectingErrorHandler);
        $files                  = [
            $this->createFile('MyBadCode.php', '<?php this causes a parse error'),
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
                'MyBadCode.php',
                'this content is not relevant because the parser is mocked and always returns null',
            ),
        ];

        $this->locate($files);
    }

    private function createFile(string $path, string|null $content = null): string
    {
        $fullPath = $this->root->path($path);
        file_put_contents($fullPath, $content);

        return $fullPath;
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
