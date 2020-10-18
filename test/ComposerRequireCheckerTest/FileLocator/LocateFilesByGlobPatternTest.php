<?php

declare(strict_types=1);

namespace ComposerRequireCheckerTest\FileLocator;

use ComposerRequireChecker\FileLocator\LocateFilesByGlobPattern;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use PHPUnit\Framework\TestCase;

final class LocateFilesByGlobPatternTest extends TestCase
{
    private LocateFilesByGlobPattern $locator;

    private vfsStreamDirectory $root;

    protected function setUp(): void
    {
        $this->locator = new LocateFilesByGlobPattern();
        $this->root    = vfsStream::setup();
    }

    public function testSimpleGlobPattern(): void
    {
        vfsStream::create([
            'bin' => [
                'console' => '',
                'not-console' => '',
            ],
        ]);

        $files = $this->files(['bin/console'], $this->root->url());
        self::assertCount(1, $files);
        self::assertContains($this->root->getChild('bin/console')->url(), $files);
    }

    public function testGlobPattern(): void
    {
        vfsStream::create([
            'bin' => [
                'console.php' => '',
                'console123.php' => '',
                'not-console' => '',
            ],
        ]);

        $files = $this->files(['bin/console*.php'], $this->root->url());
        self::assertCount(2, $files);
        self::assertContains($this->root->getChild('bin/console.php')->url(), $files);
        self::assertContains($this->root->getChild('bin/console123.php')->url(), $files);
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
            $files[] = $file;
        }

        return $files;
    }
}
