<?php

namespace ComposerRequireCheckerTest\FileLocator;

use ComposerRequireChecker\FileLocator\LocateComposerPackageSourceFiles;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use PHPUnit\Framework\TestCase;

/**
 * @covers \ComposerRequireChecker\FileLocator\LocateComposerPackageSourceFiles
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

    public function testFromClassmap()
    {
        vfsStream::create([
            'composer.json' => '{"autoload": {"classmap": ["src/MyClassA.php", "MyClassB.php"]}}',
            'src' => [
                'MyClassA.php' => '<?php class MyClassA {}',
            ],
            'MyClassB.php' => '<?php class MyClassB {}',
        ]);

        $files = $this->files($this->root->getChild('composer.json')->url());

        $this->assertCount(2, $files);
        $this->assertContains($this->root->getChild('src/MyClassA.php')->url(), $files);
        $this->assertContains($this->root->getChild('MyClassB.php')->url(), $files);
    }

    public function testFromFiles()
    {
        vfsStream::create([
            'composer.json' => '{"autoload": {"files": ["foo.php"]}}',
            'foo.php' => '<?php class MyClass {}',
        ]);

        $files = $this->files($this->root->getChild('composer.json')->url());

        $this->assertCount(1, $files);
        $this->assertContains($this->root->getChild('foo.php')->url(), $files);
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

    public function testFromPsr0WithMultipleDirectories()
    {
        vfsStream::create([
            'composer.json' => '{"autoload": {"psr-0": {"MyNamespace\\\\": ["src", "lib"]}}}',
            'src' => ['MyNamespace' => ['MyClassA.php' => '<?php namespace MyNamespace; class MyClassA {}']],
            'lib' => ['MyNamespace' => ['MyClassB.php' => '<?php namespace MyNamespace; class MyClassB {}']],
        ]);

        $files = $this->files($this->root->getChild('composer.json')->url());

        $this->assertCount(2, $files);
        $this->assertContains($this->root->getChild('src/MyNamespace/MyClassA.php')->url(), $files);
        $this->assertContains($this->root->getChild('lib/MyNamespace/MyClassB.php')->url(), $files);
    }

    public function testFromPsr4WithMultipleDirectories()
    {
        vfsStream::create([
            'composer.json' => '{"autoload": {"psr-4": {"MyNamespace\\\\": ["src", "lib"]}}}',
            'src' => ['MyClassA.php' => '<?php namespace MyNamespace; class MyClassA {}'],
            'lib' => ['MyClassB.php' => '<?php namespace MyNamespace; class MyClassB {}'],
        ]);

        $files = $this->files($this->root->getChild('composer.json')->url());

        $this->assertCount(2, $files);
        $this->assertContains($this->root->getChild('src/MyClassA.php')->url(), $files);
        $this->assertContains($this->root->getChild('lib/MyClassB.php')->url(), $files);
    }

    /**
     * @return string[]
     */
    private function files(string $composerJson)
    {
        $files = [];
        $filesGenerator = ($this->locator)($composerJson);
        foreach ($filesGenerator as $file) {
            $files[] = $file;
        }
        return $files;
    }
}
