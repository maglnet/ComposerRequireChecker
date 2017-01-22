<?php

namespace ComposerRequireCheckerTest\ASTLocator;

use ArrayObject;
use ComposerRequireChecker\ASTLocator\LocateASTFromFiles;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
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
