<?php

declare(strict_types=1);

namespace ComposerRequireCheckerTest\ASTLocator;

use ComposerRequireChecker\ASTLocator\ASTLoader;
use ComposerRequireChecker\Exception\FileParseFailed;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use PhpParser\ErrorHandler\Collecting;
use PhpParser\Lexer;
use PhpParser\Parser\Php7;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * @covers \ComposerRequireChecker\ASTLocator\ASTLoader
 */
final class ASTLoaderTest extends TestCase
{
    private ASTLoader $loader;
    private vfsStreamDirectory $root;

    protected function setUp(): void
    {
        parent::setUp();

        $this->loader = new ASTLoader(new Php7(new Lexer()), null);
        $this->root   = vfsStream::setup();
    }

    public function testFailOnParseError(): void
    {
        $this->expectException(FileParseFailed::class);
        $this->expectExceptionMessageMatches('/\[vfs:\/\/root\/MyBadCode\]/');

        $this->loader->__invoke($this->createFile('MyBadCode', '<?php this causes a parse error'));
    }

    public function testDoNotFailOnParseErrorWithErrorHandler(): void
    {
        $collectingErrorHandler = new Collecting();
        $this->loader           = new ASTLoader(new Php7(new Lexer()), $collectingErrorHandler);

        $astRoot = $this->loader->__invoke($this->createFile('MyBadCode', '<?php this causes a parse error'));
        $this->assertTrue($collectingErrorHandler->hasErrors());
        $this->assertCount(1, $collectingErrorHandler->getErrors()); //should have one parse error
    }

    public function testFailOnParseErrorWithNullReturn(): void
    {
        $this->expectException(RuntimeException::class);

        $parserMock = $this->createMock(Php7::class);
        $parserMock->method('parse')->willReturn(null);

        $this->loader = new ASTLoader($parserMock, null);

        $this->loader->__invoke($this->createFile(
            'MyBadCode',
            'this content is not relevant because the parser is mocked and always returns null'
        ));
    }

    private function createFile(string $path, ?string $content = null): string
    {
        return vfsStream::newFile($path)->at($this->root)->setContent($content)->url();
    }
}
