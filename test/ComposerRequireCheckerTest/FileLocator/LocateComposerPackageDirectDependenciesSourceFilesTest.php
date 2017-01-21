<?php

namespace ComposerRequireCheckerTest\FileLocator;

use ComposerRequireChecker\FileLocator\LocateComposerPackageDirectDependenciesSourceFiles;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use PHPUnit\Framework\TestCase;

class LocateComposerPackageDirectDependenciesSourceFilesTest extends TestCase
{
    /** @var LocateComposerPackageDirectDependenciesSourceFiles */
    private $locator;
    /** @var vfsStreamDirectory */
    private $root;

    protected function setUp()
    {
        parent::setUp();

        $this->locator = new LocateComposerPackageDirectDependenciesSourceFiles();
        $this->root = vfsStream::setup();
    }

    public function testNoDependencies()
    {
        $composerJson = vfsStream::newFile('composer.json')->at($this->root)->withContent('{}')->url();

        $files = $this->locate($composerJson);

        $this->assertCount(0, $files);
    }

    public function testSingleDependency()
    {
        vfsStream::create([
            'composer.json' => '{"require":{"foo/bar": "^1.0"}}',
            'vendor' => [
                'foo' => [
                    'bar' => [
                        'composer.json' => '{"autoload":{"psr-4":{"":"src"}}}',
                        'src' => [
                            'MyClass.php' => '',
                        ],
                    ],
                ],
            ],
        ]);

        $files = $this->locate($this->root->getChild('composer.json')->url());

        $this->assertCount(1, $files);

        $expectedFile = $this->root->getChild('vendor/foo/bar/src/MyClass.php')->url();
        $actualFile = str_replace('\\', '/', reset($files));
        $this->assertSame($expectedFile, $actualFile);
    }

    public function testVendorDirsWithoutComposerFilesAreIgnored()
    {
        vfsStream::create([
            'composer.json' => '{"require": {"foo/bar": "^1.0"}}',
            'vendor' => [
                'foo' => [
                    'bar' => [
                        'src' => [
                            'MyClass.php' => '',
                        ],
                    ],
                ],
            ],
        ]);

        $files = $this->locate($this->root->getChild('composer.json')->url());

        $this->assertCount(0, $files);
    }

    /**
     * @return string[]
     */
    private function locate(string $composerJson): array
    {
        return iterator_to_array(($this->locator)($composerJson));
    }
}
