<?php

declare(strict_types=1);

namespace ComposerRequireCheckerTest\FileLocator;

use ComposerRequireChecker\FileLocator\LocateFilesByGlobPattern;
use PHPUnit\Framework\TestCase;
use Spatie\TemporaryDirectory\TemporaryDirectory;

use function array_map;
use function realpath;
use function str_replace;
use function touch;

use const DIRECTORY_SEPARATOR;

final class LocateFilesByGlobPatternTest extends TestCase
{
    private LocateFilesByGlobPattern $locator;

    private TemporaryDirectory $root;

    protected function setUp(): void
    {
        $this->locator = new LocateFilesByGlobPattern();
        $this->root    = (new TemporaryDirectory())
            ->deleteWhenDestroyed()
            ->create();
    }

    public function testSimpleGlobPattern(): void
    {
        touch($this->path('bin/console.php'));
        touch($this->path('bin/not-console.php'));

        $files = $this->files(['bin/console.php'], $this->root->path());
        self::assertCount(1, $files);
        self::assertContains($this->path('bin/console.php'), $files);
    }

    public function testGlobPattern(): void
    {
        touch($this->path('bin/console.php'));
        touch($this->path('bin/console123.php'));
        touch($this->path('bin/not-console.php'));

        $files = $this->files(['bin/console*.php'], $this->root->path() . '/');
        $files = array_map('realpath', $files);

        self::assertCount(2, $files);
        self::assertContains(realpath($this->path('bin/console.php')), $files);
        self::assertContains(realpath($this->path('bin/console123.php')), $files);
    }

    public function testNoMatchesEmptyDirectoryEmptyGlob(): void
    {
        $files = $this->files([], $this->root->path() . '/');

        self::assertCount(0, $files);
    }

    public function testNoMatchesEmptyDirectory(): void
    {
        $files = $this->files(['bin/console*.php'], $this->root->path() . '/');

        self::assertCount(0, $files);
    }

    public function testNoDoubleDirectorySeparator(): void
    {
        touch($this->path('bin/console.php'));
        touch($this->path('bin/console123.php'));
        touch($this->path('bin/not-console.php'));

        $files = $this->files(['bin/console*.php'], $this->root->path() . '/');

        foreach ($files as $file) {
            $this->assertStringNotContainsString(DIRECTORY_SEPARATOR . DIRECTORY_SEPARATOR, $file);
        }

        self::assertCount(2, $files);
    }

    /**
     * @param array<string> $globPatterns
     *
     * @return array<string>
     */
    private function files(array $globPatterns, string $dir): array
    {
        $files          = [];
        $filesGenerator = ($this->locator)($globPatterns, $dir);
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
