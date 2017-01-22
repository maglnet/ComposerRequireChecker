<?php

namespace ComposerRequireCheckerTest\FileLocator;

use ComposerRequireChecker\FileLocator\LocateComposerPackageSourceFiles;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use PHPUnit\Framework\TestCase;

/**
 * @covers \ComposerRequireChecker\FileLocator\LocateComposerPackageSourceFiles
 * @todo We're missing tests for the "classmap" and "files" keys.
 */
class LocateComposerPackageSourceFilesTest extends TestCase
{
    /** @var LocateComposerPackageSourceFiles */
    private $locator;
    /** @var vfsStreamDirectory */
    private $root;

    protected function setUp()
    {
        parent::setUp();

        $this->locator = new LocateComposerPackageSourceFiles();
        $this->root = vfsStream::setup();
    }

    public function testFromPsr0()
    {
        vfsStream::create([
            'composer.json' => '{"autoload": {"psr-0": ["src"]}}',
            'src' => [
                'MyNamespace' => [
                    'MyClass.php' => '<?php namespace MyNamespace; class MyClass {}',
                ],
            ],
        ]);

        $files = $this->files($this->root->getChild('composer.json')->url());

        $this->assertCount(1, $files);
    }

    public function testFromPsr4()
    {
        vfsStream::create([
            'composer.json' => '{"autoload": {"psr-4": {"MyNamespace\\\\": "src"}}}',
            'src' => [
                'MyClass.php' => '<?php namespace MyNamespace; class MyClass {}',
            ],
        ]);

        $files = $this->files($this->root->getChild('composer.json')->url());

        $this->assertCount(1, $files);
    }

    /**
     * @return string[]
     */
    private function files(string $composerJson)
    {
        return iterator_to_array(($this->locator)($composerJson));
    }
}
