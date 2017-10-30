<?php

namespace ComposerRequireCheckerTest\ASTLocator;

use ArrayObject;
use ComposerRequireChecker\ASTLocator\LocateASTFromFiles;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use PhpParser\Error;
use PhpParser\ErrorHandler\Collecting;
use PhpParser\Lexer;
use PhpParser\Parser\Php7;
use PHPUnit\Framework\TestCase;

/**
 * @covers \ComposerRequireChecker\ASTLocator\LocateASTFromFiles
 */
class LocateASTFromFilesTest extends TestCase
{
    /** @var LocateASTFromFiles */
    private $locator;
    /** @var vfsStreamDirectory */
    private $root;

    protected function setUp()
    {
        parent::setUp();

        $this->locator = new LocateASTFromFiles(new Php7(new Lexer()));
        $this->root = vfsStream::setup();
    }

    public function testLocate()
    {
        $files = [
            $this->createFile('MyClassA', '<?php class MyClassA {}'),
            $this->createFile('MyClassB', '<?php class MyClassB {}'),
        ];

        $roots = $this->locate($files);

        $this->assertCount(2, $roots);
    }

    public function testFailOnParseError()
    {
        self::expectException(Error::class);
        $files = [
            $this->createFile('MyBadCode', '<?php this causes a parse error'),
        ];

        $this->locate($files);
    }

    public function testDoNotFailOnParseErrorWithErrorHandler()
    {
        $collectingErrorHandler = new Collecting();
        $this->locator = new LocateASTFromFiles(new Php7(new Lexer()), $collectingErrorHandler);
        $files = [
            $this->createFile('MyBadCode', '<?php this causes a parse error'),
        ];

        $roots = $this->locate($files);
        $this->assertCount(1, $roots); // one file should be parsed (partially)
        $this->assertTrue($collectingErrorHandler->hasErrors());
        $this->assertCount(1, $collectingErrorHandler->getErrors()); //should have one parse error
    }

    private function createFile(string $path, string $content = null): string
    {
        return vfsStream::newFile($path)->at($this->root)->setContent($content)->url();
    }

    /**
     * @param string[] $files
     */
    private function locate(array $files): array
    {
        return iterator_to_array(($this->locator)(new ArrayObject($files)));
    }
}
