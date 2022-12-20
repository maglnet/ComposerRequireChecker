<?php

declare(strict_types=1);

namespace ComposerRequireCheckerTest\FileLocator;

use ComposerRequireChecker\FileLocator\LocateComposerPackageSourceFiles;
use ComposerRequireChecker\JsonLoader;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use PHPUnit\Framework\TestCase;

use function count;
use function dirname;
use function json_encode;
use function sprintf;
use function str_replace;

use const JSON_THROW_ON_ERROR;

/** @covers \ComposerRequireChecker\FileLocator\LocateComposerPackageSourceFiles */
final class LocateComposerPackageSourceFilesTest extends TestCase
{
    private LocateComposerPackageSourceFiles $locator;
    private vfsStreamDirectory $root;

    protected function setUp(): void
    {
        parent::setUp();

        $this->locator = new LocateComposerPackageSourceFiles();
        $this->root    = vfsStream::setup();
    }

    public function testFromClassmap(): void
    {
        vfsStream::create([
            'composer.json' => '{"autoload": {"classmap": ["src/MyClassA.php", "MyClassB.php"]}}',
            'src' => ['MyClassA.php' => '<?php class MyClassA {}'],
            'MyClassB.php' => '<?php class MyClassB {}',
        ]);

        $files = $this->files($this->root->getChild('composer.json')->url());

        $this->assertCount(2, $files);
        $this->assertContains($this->root->getChild('src/MyClassA.php')->url(), $files);
        $this->assertContains($this->root->getChild('MyClassB.php')->url(), $files);
    }

    public function testFromFiles(): void
    {
        vfsStream::create([
            'composer.json' => '{"autoload": {"files": ["foo.php"]}}',
            'foo.php' => '<?php class MyClass {}',
        ]);

        $files = $this->files($this->root->getChild('composer.json')->url());

        $this->assertCount(1, $files);
        $this->assertContains($this->root->getChild('foo.php')->url(), $files);
    }

    public function testFromPsr0(): void
    {
        vfsStream::create([
            'composer.json' => '{"autoload": {"psr-0": ["src"]}}',
            'src' => [
                'MyNamespace' => ['MyClass.php' => '<?php namespace MyNamespace; class MyClass {}'],
            ],
        ]);

        $files = $this->files($this->root->getChild('composer.json')->url());

        $this->assertCount(1, $files);
        $this->assertContains($this->root->getChild('src/MyNamespace/MyClass.php')->url(), $files);
    }

    public function testFromPsr4(): void
    {
        vfsStream::create([
            'composer.json' => '{"autoload": {"psr-4": {"MyNamespace\\\\": "src"}}}',
            'src' => ['MyClass.php' => '<?php namespace MyNamespace; class MyClass {}'],
        ]);

        $files = $this->files($this->root->getChild('composer.json')->url());

        $this->assertCount(1, $files);
        $this->assertContains($this->root->getChild('src/MyClass.php')->url(), $files);
    }

    public function testFromPsr0WithMultipleDirectories(): void
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

    public function testFromPsr4WithMultipleDirectories(): void
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

    public function testFromPsr4WithNestedDirectory(): void
    {
        vfsStream::create([
            'composer.json' => '{"autoload": {"psr-4": {"MyNamespace\\\\": ["src/MyNamespace"]}}}',
            'src' => [
                'MyNamespace' => ['MyClassA.php' => '<?php namespace MyNamespace; class MyClassA {}'],
            ],
        ]);

        $files = $this->files($this->root->getChild('composer.json')->url());

        $this->assertCount(1, $files);
        $this->assertContains($this->root->getChild('src/MyNamespace/MyClassA.php')->url(), $files);
    }

    public function testFromPsr4WithNestedDirectoryAlternativeDirectorySeparator(): void
    {
        vfsStream::create([
            'composer.json' => '{"autoload": {"psr-4": {"MyNamespace\\\\": ["src\\\\MyNamespace"]}}}',
            'src' => [
                'MyNamespace' => ['MyClassA.php' => '<?php namespace MyNamespace; class MyClassA {}'],
            ],
        ]);

        $files = $this->files($this->root->getChild('composer.json')->url());

        $this->assertCount(1, $files);
        $this->assertContains($this->root->getChild('src/MyNamespace/MyClassA.php')->url(), $files);
    }

    /**
     * @param array<string> $excludedPattern
     * @param array<string> $expectedFiles
     *
     * @dataProvider provideExcludePattern
     */
    public function testFromPsr4WithExcludeFromClassmap(array $excludedPattern, array $expectedFiles): void
    {
        $excludedPatternJson = json_encode($excludedPattern, JSON_THROW_ON_ERROR);

        vfsStream::create([
            'composer.json' => sprintf(
                '{"autoload": {"psr-4": {"MyNamespace\\\\": ""}, "exclude-from-classmap": %s}}',
                $excludedPatternJson,
            ),
            'ClassA.php' => '<?php namespace MyNamespace; class ClassA {}',
            'tests' => ['ATest.php' => '<?php namespace MyNamespace; class ATest {}'],
            'foo' => [
                'Bar' => ['BTest.php' => '<?php namespace MyNamespace; class BTest {}'],
                'src' => [
                    'ClassB.php' => '<?php namespace MyNamespace; class ClassB {}',
                    'Bar' => ['CTest.php' => '<?php namespace MyNamespace; class CTest {}'],
                ],
            ],
        ]);

        $files = $this->files($this->root->getChild('composer.json')->url());

        $this->assertCount(count($expectedFiles), $files);
        foreach ($expectedFiles as $expectedFile) {
            $this->assertContains($this->root->getChild($expectedFile)->url(), $files);
        }
    }

    /** @return array<string, array<array<string>>> */
    public function provideExcludePattern(): array
    {
        return [
            'No exclude pattern' => [
                [],
                [
                    'ClassA.php',
                    'tests/ATest.php',
                    'foo/Bar/BTest.php',
                    'foo/src/ClassB.php',
                    'foo/src/Bar/CTest.php',
                ],
            ],
            'Exclude single directory by pattern' => [
                ['/tests/'],
                [
                    'ClassA.php',
                    'foo/Bar/BTest.php',
                    'foo/src/ClassB.php',
                    'foo/src/Bar/CTest.php',
                ],
            ],
            'Exclude all subdirectories by pattern' => [
                ['**/Bar/'],
                [
                    'ClassA.php',
                    'tests/ATest.php',
                    'foo/src/ClassB.php',
                ],
            ],
            'Combine multiple patterns' => [
                ['/tests/', '**/Bar/'],
                [
                    'ClassA.php',
                    'foo/src/ClassB.php',
                ],
            ],
        ];
    }

    /** @return string[] */
    private function files(string $composerJson): array
    {
        $composerData   = JsonLoader::getData($composerJson);
        $files          = [];
        $filesGenerator = ($this->locator)($composerData, dirname($composerJson));
        foreach ($filesGenerator as $file) {
            $files[] = str_replace('\\', '/', $file);
        }

        return $files;
    }
}
