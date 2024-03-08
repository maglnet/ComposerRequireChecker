<?php

declare(strict_types=1);

namespace ComposerRequireCheckerTest\FileLocator;

use ArrayObject;
use ComposerRequireChecker\FileLocator\LocateAllFilesByExtension;
use PHPUnit\Framework\TestCase;
use Spatie\TemporaryDirectory\TemporaryDirectory;

use function count;
use function file_put_contents;
use function sprintf;
use function str_replace;
use function touch;

use const DIRECTORY_SEPARATOR;

/** @covers \ComposerRequireChecker\FileLocator\LocateAllFilesByExtension */
final class LocateAllFilesByExtensionTest extends TestCase
{
    private LocateAllFilesByExtension $locator;
    private TemporaryDirectory $root;

    protected function setUp(): void
    {
        parent::setUp();

        $this->locator = new LocateAllFilesByExtension();
        $this->root    = (new TemporaryDirectory())
            ->deleteWhenDestroyed()
            ->create();
    }

    public function testLocateFromNoDirectories(): void
    {
        $files = $this->locate([], '.php', null);

        $this->assertCount(0, $files);
    }

    public function testLocateFromASingleDirectory(): void
    {
        $dir   = $this->path('MyNamespaceA');
        $files = [];
        for ($i = 0; $i < 3; $i++) {
            $fileName = sprintf('MyClass%d.php', $i);
            $filePath = $dir . DIRECTORY_SEPARATOR . $fileName;
            touch($filePath);
            $files[] = $filePath;
        }

        $foundFiles = $this->locate([$dir], '.php', null);
        $foundFiles = str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $foundFiles);

        $this->assertCount(count($files), $foundFiles);
        foreach ($files as $file) {
            $this->assertContains($file, $foundFiles);
        }
    }

    public function testLocateWithFilenameBlackList(): void
    {
        $dir = $this->path('MyNamespaceA');
        for ($i = 0; $i < 10; $i++) {
            $fileName = sprintf('MyClass%d.php', $i);
            $filePath = $dir . DIRECTORY_SEPARATOR . $fileName;
            touch($filePath);
            $files[] = $filePath;
        }

        $foundFiles = $this->locate([$dir], '.php', ['MyClass6']);

        $this->assertCount(9, $foundFiles);
        $this->assertNotContains($this->path('MyClass6.php'), $foundFiles);
    }

    public function testLocateWithDirectoryBlackList(): void
    {
        $dir = $this->path('MyNamespaceA');
        for ($i = 0; $i < 10; $i++) {
            $filePath = $this->path(sprintf('MyNamespaceA/Directory%d/MyClass.php', $i));
            touch($filePath);
        }

        $foundFiles = $this->locate([$dir], '.php', ['Directory5/']);

        $this->assertCount(9, $foundFiles);
        $this->assertNotContains($this->path('MyNamespaceA/Directory5/MyClass.php'), $foundFiles);
    }

    /**
     * @param array<string> $blacklist
     * @param array<string> $expectedFiles
     *
     * @dataProvider provideBlacklists
     */
    public function testLocateWithBlackList(array $blacklist, array $expectedFiles): void
    {
        file_put_contents($this->path('MyNamespaceA/MyClass.php'), '<?php class MyClass {}');
        file_put_contents($this->path('MyNamespaceA/Foo/FooClass.php'), '<?php class FooCalls {}');
        file_put_contents($this->path('MyNamespaceA/Foo/Bar/BarClass.php'), '<?php class BarClass {}');
        file_put_contents($this->path('MyNamespaceA/Bar/AnotherBarClass.php'), '<?php class AnotherBarClass {}');

        $foundFiles = $this->locate([$this->root->path()], '.php', $blacklist);

        $this->assertCount(count($expectedFiles), $foundFiles);
        foreach ($expectedFiles as $file) {
            $this->assertContains($this->path($file), $foundFiles);
        }

        $this->assertContains($this->path('MyNamespaceA/Foo/FooClass.php'), $foundFiles);
    }

    /** @return array<string, array<array<string>>> */
    public static function provideBlacklists(): array
    {
        return [
            'No blacklist' => [
                [],
                [
                    'MyNamespaceA/MyClass.php',
                    'MyNamespaceA/Foo/FooClass.php',
                    'MyNamespaceA/Foo/Bar/BarClass.php',
                    'MyNamespaceA/Bar/AnotherBarClass.php',
                ],
            ],
            '* wildcard' => [
                ['Another*.php'],
                [
                    'MyNamespaceA/MyClass.php',
                    'MyNamespaceA/Foo/FooClass.php',
                    'MyNamespaceA/Foo/Bar/BarClass.php',
                ],
            ],
            '** wildcard' => [
                ['**/Bar'],
                [
                    'MyNamespaceA/MyClass.php',
                    'MyNamespaceA/Foo/FooClass.php',
                ],
            ],
            'Combined patterns' => [
                ['My*.php', 'Bar/'],
                ['MyNamespaceA/Foo/FooClass.php'],
            ],
        ];
    }

    /**
     * @param array<string>      $directories
     * @param array<string>|null $blacklist
     *
     * @return array<string>
     */
    private function locate(array $directories, string $fileExtension, array|null $blacklist): array
    {
        $files = [];
        foreach (($this->locator)(new ArrayObject($directories), $fileExtension, $blacklist) as $file) {
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
