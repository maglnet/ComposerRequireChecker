<?php

namespace ComposerRequireCheckerTest\FileLocator;

use ArrayObject;
use ComposerRequireChecker\FileLocator\LocateAllFilesByExtension;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use PHPUnit\Framework\TestCase;

/**
 * @covers \ComposerRequireChecker\FileLocator\LocateAllFilesByExtension
 */
final class LocateAllFilesByExtensionTest extends TestCase
{
    /** @var LocateAllFilesByExtension */
    private $locator;
    /** @var vfsStreamDirectory */
    private $root;

    protected function setUp(): void
    {
        parent::setUp();

        $this->locator = new LocateAllFilesByExtension();
        $this->root = vfsStream::setup();
    }

    public function testLocateFromNoDirectories(): void
    {
        $files = $this->locate([], '.php', null);

        $this->assertCount(0, $files);
    }

    public function testLocateFromASingleDirectory(): void
    {
        $dir = vfsStream::newDirectory('MyNamespaceA')->at($this->root);
        $files = [];
        for ($i = 0; $i < 3; $i++) {
            $files[] = vfsStream::newFile("MyClass$i.php")->at($dir);
        }

        $foundFiles = $this->locate([$dir->url()], '.php', null);

        $this->assertCount(count($files), $foundFiles);
        foreach ($files as $file) {
            $this->assertContains($file->url(), str_replace('\\', '/', $foundFiles));
        }
    }

    public function testLocateWithFilenameBlackList(): void
    {
        $dir = vfsStream::newDirectory('MyNamespaceA')->at($this->root);
        for ($i = 0; $i < 10; $i++) {
            vfsStream::newFile("MyClass$i.php")->at($dir);
        }

        $foundFiles = $this->locate([$dir->url()], '.php', ['MyClass6']);

        $this->assertCount(9, $foundFiles);
        $this->assertNotContains(vfsStream::url('MyClass6.php'), $foundFiles);
    }

    public function testLocateWithDirectoryBlackList(): void
    {
        $dir = vfsStream::newDirectory('MyNamespaceA')->at($this->root);
        for ($i = 0; $i < 10; $i++) {
            vfsStream::newFile("Directory$i/MyClass.php")->at($dir);
        }

        $foundFiles = $this->locate([$dir->url()], '.php', ['Directory5/']);

        $this->assertCount(9, $foundFiles);
        $this->assertNotContains(vfsStream::url('Directory5/MyClass.php'), $foundFiles);
    }

    /**
     * @param array $blacklist
     * @param array $expectedFiles
     *
     * @dataProvider provideBlacklists
     */
    public function testLocateWithBlackList(array $blacklist, array $expectedFiles): void
    {
        $this->root = vfsStream::create([
            'MyNamespaceA' => [
                'MyClass.php' => '<?php class MyClass {}',
                'Foo' => [
                    'FooClass.php' => '<?php class FooCalls {}',
                    'Bar' => [
                        'BarClass.php' => '<?php class BarClass {}',
                    ]
                ],
                'Bar' => [
                    'AnotherBarClass.php' => '<?php class AnotherBarClass {}',
                ]
            ]
        ]);

        $foundFiles = $this->locate([$this->root->url()], '.php', $blacklist);

        $this->assertCount(count($expectedFiles), $foundFiles);
        foreach ($expectedFiles as $file) {
            $this->assertContains($this->root->getChild($file)->url(), $foundFiles);
        }
        $this->assertContains($this->root->getChild('MyNamespaceA/Foo/FooClass.php')->url(), $foundFiles);
    }

    public function provideBlacklists(): array {
        return [
            'No blacklist' => [
                [],
                [
                    'MyNamespaceA/MyClass.php',
                    'MyNamespaceA/Foo/FooClass.php',
                    'MyNamespaceA/Foo/Bar/BarClass.php',
                    'MyNamespaceA/Bar/AnotherBarClass.php',
                ]
            ],
            '* wildcard' => [
                ['Another*.php'],
                [
                    'MyNamespaceA/MyClass.php',
                    'MyNamespaceA/Foo/FooClass.php',
                    'MyNamespaceA/Foo/Bar/BarClass.php',
                ]
            ],
            '** wildcard' => [
                ['**/Bar'],
                [
                    'MyNamespaceA/MyClass.php',
                    'MyNamespaceA/Foo/FooClass.php',
                ]
            ],
            'Combined patterns' => [
                ['My*.php', 'Bar/'],
                [
                    'MyNamespaceA/Foo/FooClass.php',
                ]
            ],
        ];
    }

    /**
     * @param string[] $directories
     * @param string $fileExtension
     * @param array|null $blacklist
     * @return array|\string[]
     */
    private function locate(array $directories, string $fileExtension, ?array $blacklist): array
    {
        $files = [];
        foreach (($this->locator)(new ArrayObject($directories), $fileExtension, $blacklist) as $file) {
            $files[] = str_replace('\\', '/', $file);
        }
        return $files;
    }
}
