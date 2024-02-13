<?php

declare(strict_types=1);

namespace ComposerRequireCheckerTest\FileLocator;

use ComposerRequireChecker\FileLocator\LocateComposerPackageSourceFiles;
use ComposerRequireChecker\JsonLoader;
use PHPUnit\Framework\TestCase;
use Spatie\TemporaryDirectory\TemporaryDirectory;

use function count;
use function dirname;
use function file_put_contents;
use function json_encode;
use function sprintf;
use function str_replace;

use const DIRECTORY_SEPARATOR;
use const JSON_THROW_ON_ERROR;

/** @covers \ComposerRequireChecker\FileLocator\LocateComposerPackageSourceFiles */
final class LocateComposerPackageSourceFilesTest extends TestCase
{
    private LocateComposerPackageSourceFiles $locator;
    private TemporaryDirectory $root;

    protected function setUp(): void
    {
        parent::setUp();

        $this->locator = new LocateComposerPackageSourceFiles();
        $this->root    = (new TemporaryDirectory())
            ->deleteWhenDestroyed()
            ->create();
    }

    public function testFromClassmap(): void
    {
        file_put_contents($this->path('composer.json'), '{"autoload": {"classmap": ["src/MyClassA.php", "MyClassB.php"]}}');
        file_put_contents($this->path('src/MyClassA.php'), '<?php class MyClassA {}');
        file_put_contents($this->path('MyClassB.php'), '<?php class MyClassB {}');

        $files = $this->files($this->path('composer.json'));

        $this->assertCount(2, $files);
        $this->assertContains($this->path('src/MyClassA.php'), $files);
        $this->assertContains($this->path('MyClassB.php'), $files);
    }

    public function testFromClassmapWithStrangePaths(): void
    {
        file_put_contents($this->path('composer.json'), '{"autoload": {"classmap": ["/src/MyClassA.php", "MyClassB.php"]}}');
        file_put_contents($this->path('src/MyClassA.php'), '<?php class MyClassA {}');
        file_put_contents($this->path('MyClassB.php'), '<?php class MyClassB {}');

        $files = $this->files($this->path('composer.json'));

        $this->assertCount(2, $files);
        $this->assertContains($this->path('src/MyClassA.php'), $files);
        $this->assertContains($this->path('MyClassB.php'), $files);
    }

    public function testFromFiles(): void
    {
        file_put_contents($this->path('composer.json'), '{"autoload": {"files": ["foo.php"]}}');
        file_put_contents($this->path('foo.php'), '<?php class MyClass {}');

        $files = $this->files($this->path('composer.json'));

        $this->assertCount(1, $files);
        $this->assertContains($this->path('foo.php'), $files);
    }

    public function testFromPsr0(): void
    {
        file_put_contents($this->path('composer.json'), '{"autoload": {"psr-0": ["src"]}}');
        file_put_contents($this->path('src/MyNamespace/MyClass.php'), '<?php namespace MyNamespace; class MyClass {}');

        $files = $this->files($this->path('composer.json'));

        $this->assertCount(1, $files);
        $this->assertContains($this->path('src/MyNamespace/MyClass.php'), $files);
    }

    public function testFromPsr4(): void
    {
        file_put_contents($this->path('composer.json'), '{"autoload": {"psr-4": {"MyNamespace\\\\": "src"}}}');
        file_put_contents($this->path('src/MyClass.php'), '<?php namespace MyNamespace; class MyClass {}');

        $files = $this->files($this->path('composer.json'));

        $this->assertCount(1, $files);
        $this->assertContains($this->path('src/MyClass.php'), $files);
    }

    public function testFromPsr0WithMultipleDirectories(): void
    {
        file_put_contents($this->path('composer.json'), '{"autoload": {"psr-0": {"MyNamespace\\\\": ["src", "lib"]}}}');
        file_put_contents($this->path('src/MyNamespace/MyClassA.php'), '<?php namespace MyNamespace; class MyClassA {}');
        file_put_contents($this->path('lib/MyNamespace/MyClassB.php'), '<?php namespace MyNamespace; class MyClassB {}');

        $files = $this->files($this->path('composer.json'));

        $this->assertCount(2, $files);
        $this->assertContains($this->path('src/MyNamespace/MyClassA.php'), $files);
        $this->assertContains($this->path('lib/MyNamespace/MyClassB.php'), $files);
    }

    public function testFromPsr4WithMultipleDirectories(): void
    {
        file_put_contents($this->path('composer.json'), '{"autoload": {"psr-4": {"MyNamespace\\\\": ["src", "lib"]}}}');
        file_put_contents($this->path('src/MyClassA.php'), '<?php namespace MyNamespace; class MyClassA {}');
        file_put_contents($this->path('lib/MyClassB.php'), '<?php namespace MyNamespace; class MyClassB {}');

        $files = $this->files($this->path('composer.json'));

        $this->assertCount(2, $files);
        $this->assertContains($this->path('src/MyClassA.php'), $files);
        $this->assertContains($this->path('lib/MyClassB.php'), $files);
    }

    public function testFromPsr4WithNestedDirectory(): void
    {
        file_put_contents($this->path('composer.json'), '{"autoload": {"psr-4": {"MyNamespace\\\\": ["src/MyNamespace"]}}}');
        file_put_contents($this->path('src/MyNamespace/MyClassA.php'), '<?php namespace MyNamespace; class MyClassA {}');

        $files = $this->files($this->path('composer.json'));

        $this->assertCount(1, $files);
        $this->assertContains($this->path('src/MyNamespace/MyClassA.php'), $files);
    }

    public function testFromPsr4WithNestedDirectoryAlternativeDirectorySeparator(): void
    {
            file_put_contents($this->path('composer.json'), '{"autoload": {"psr-4": {"MyNamespace\\\\": ["src\\\\MyNamespace"]}}}');
            file_put_contents($this->path('src/MyNamespace/MyClassA.php'), '<?php namespace MyNamespace; class MyClassA {}');

        $files = $this->files($this->path('composer.json'));

        $this->assertCount(1, $files);
        $this->assertContains($this->path('src/MyNamespace/MyClassA.php'), $files);
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

        file_put_contents($this->path('composer.json'), sprintf('{"autoload": {"psr-4": {"MyNamespace\\\\": ""}, "exclude-from-classmap": %s}}', $excludedPatternJson));
        file_put_contents($this->path('ClassA.php'), '<?php namespace MyNamespace; class ClassA {}');
        file_put_contents($this->path('tests/ATest.php'), '<?php namespace MyNamespace; class ATest {}');
        file_put_contents($this->path('foo/Bar/BTest.php'), '<?php namespace MyNamespace; class BTest {}');
        file_put_contents($this->path('foo/src/ClassB.php'), '<?php namespace MyNamespace; class ClassB {}');
        file_put_contents($this->path('foo/src/Bar/CTest.php'), '<?php namespace MyNamespace; class CTest {}');

        $files = $this->files($this->path('composer.json'));

        $this->assertCount(count($expectedFiles), $files);
        foreach ($expectedFiles as $expectedFile) {
            $this->assertContains($this->path($expectedFile), $files);
        }
    }

    /** @return array<string, array<array<string>>> */
    public static function provideExcludePattern(): array
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
            $files[] = str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $file);
        }

        return $files;
    }

    private function path(string $path): string
    {
        $path = str_replace('/', DIRECTORY_SEPARATOR, $path);

        return $this->root->path($path);
    }
}
