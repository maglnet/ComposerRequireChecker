<?php

declare(strict_types=1);

namespace ComposerRequireCheckerTest\FileLocator;

use ComposerRequireChecker\FileLocator\LocateFilesViaRequireStatements;
use Override;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Spatie\TemporaryDirectory\TemporaryDirectory;

use function array_map;
use function file_put_contents;
use function iterator_to_array;
use function str_replace;

use const DIRECTORY_SEPARATOR;

#[CoversClass(LocateFilesViaRequireStatements::class)]
final class LocateFilesViaRequireStatementsTest extends TestCase
{
    private LocateFilesViaRequireStatements $locator;
    private TemporaryDirectory $root;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->locator = new LocateFilesViaRequireStatements();
        $this->root    = (new TemporaryDirectory())
            ->deleteWhenDestroyed()
            ->create();
    }

    public function testNonExistentFileYieldsNothing(): void
    {
        $files = $this->locate($this->path('nonexistent.php'));

        $this->assertCount(0, $files);
    }

    public function testFileWithNoRequiresYieldsNothing(): void
    {
        file_put_contents($this->path('bootstrap.php'), '<?php function foo() {}');

        $files = $this->locate($this->path('bootstrap.php'));

        $this->assertCount(0, $files);
    }

    public function testFollowsTopLevelDirConcatRequire(): void
    {
        file_put_contents(
            $this->path('bootstrap.php'),
            '<?php require __DIR__ . \'/functions.php\';',
        );
        file_put_contents($this->path('functions.php'), '<?php function foo() {}');

        $files = $this->locate($this->path('bootstrap.php'));

        $this->assertCount(1, $files);
        $this->assertContains($this->path('functions.php'), $this->normalizeFiles($files));
    }

    public function testFollowsTopLevelRequireOnce(): void
    {
        file_put_contents(
            $this->path('bootstrap.php'),
            '<?php require_once __DIR__ . \'/functions.php\';',
        );
        file_put_contents($this->path('functions.php'), '<?php function foo() {}');

        $files = $this->locate($this->path('bootstrap.php'));

        $this->assertCount(1, $files);
        $this->assertContains($this->path('functions.php'), $this->normalizeFiles($files));
    }

    public function testFollowsPlainStringRequire(): void
    {
        file_put_contents(
            $this->path('bootstrap.php'),
            '<?php require \'functions.php\';',
        );
        file_put_contents($this->path('functions.php'), '<?php function foo() {}');

        $files = $this->locate($this->path('bootstrap.php'));

        $this->assertCount(1, $files);
        $this->assertContains($this->path('functions.php'), $this->normalizeFiles($files));
    }

    public function testFollowsRequireInsideIfBlock(): void
    {
        file_put_contents(
            $this->path('bootstrap.php'),
            <<<'PHP'
<?php
if (str_starts_with(PHP_VERSION, "8.1.")) {
    require_once __DIR__ . '/8.1/functions.php';
}
if (str_starts_with(PHP_VERSION, "8.2.")) {
    require_once __DIR__ . '/8.2/functions.php';
}
PHP,
        );
        file_put_contents($this->path('8.1/functions.php'), '<?php function foo81() {}');
        file_put_contents($this->path('8.2/functions.php'), '<?php function foo82() {}');

        $files = $this->normalizeFiles($this->locate($this->path('bootstrap.php')));

        $this->assertContains($this->path('8.1/functions.php'), $files);
        $this->assertContains($this->path('8.2/functions.php'), $files);
    }

    public function testFollowsReturnRequireInsideIfBlock(): void
    {
        file_put_contents(
            $this->path('bootstrap.php'),
            <<<'PHP'
<?php
if (\PHP_VERSION_ID >= 80000) {
    return require __DIR__ . '/bootstrap80.php';
}
function legacyFoo() {}
PHP,
        );
        file_put_contents($this->path('bootstrap80.php'), '<?php function modernFoo() {}');

        $files = $this->normalizeFiles($this->locate($this->path('bootstrap.php')));

        $this->assertContains($this->path('bootstrap80.php'), $files);
    }

    public function testMultipleRequiresYieldsMultipleFiles(): void
    {
        file_put_contents(
            $this->path('bootstrap.php'),
            <<<'PHP'
<?php
require __DIR__ . '/a.php';
require __DIR__ . '/b.php';
require __DIR__ . '/c.php';
PHP,
        );
        file_put_contents($this->path('a.php'), '<?php function a() {}');
        file_put_contents($this->path('b.php'), '<?php function b() {}');
        file_put_contents($this->path('c.php'), '<?php function c() {}');

        $files = $this->locate($this->path('bootstrap.php'));

        $this->assertCount(3, $files);
    }

    public function testNonExistentRequiredFileIsSkipped(): void
    {
        file_put_contents(
            $this->path('bootstrap.php'),
            '<?php require __DIR__ . \'/does_not_exist.php\';',
        );

        $files = $this->locate($this->path('bootstrap.php'));

        $this->assertCount(0, $files);
    }

    public function testDynamicRequireWithVariableIsIgnored(): void
    {
        file_put_contents(
            $this->path('bootstrap.php'),
            '<?php $file = "functions.php"; require $file;',
        );
        file_put_contents($this->path('functions.php'), '<?php function foo() {}');

        $files = $this->locate($this->path('bootstrap.php'));

        $this->assertCount(0, $files);
    }

    /** @return string[] */
    private function locate(string $filePath): array
    {
        return iterator_to_array(($this->locator)($filePath));
    }

    /** @param string[] $files */
    private function normalizeFiles(array $files): array
    {
        return array_map(
            static fn (string $f): string => str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $f),
            $files,
        );
    }

    private function path(string $path): string
    {
        $path = str_replace('/', DIRECTORY_SEPARATOR, $path);

        return $this->root->path($path);
    }
}
