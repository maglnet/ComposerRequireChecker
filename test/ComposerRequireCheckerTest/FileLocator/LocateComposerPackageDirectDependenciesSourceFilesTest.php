<?php

declare(strict_types=1);

namespace ComposerRequireCheckerTest\FileLocator;

use ComposerRequireChecker\Exception\DependenciesNotInstalled;
use ComposerRequireChecker\FileLocator\LocateComposerPackageDirectDependenciesSourceFiles;
use PHPUnit\Framework\TestCase;
use Spatie\TemporaryDirectory\TemporaryDirectory;

use function file_exists;
use function file_put_contents;
use function iterator_to_array;
use function reset;
use function str_replace;

use const DIRECTORY_SEPARATOR;

/** @covers \ComposerRequireChecker\FileLocator\LocateComposerPackageDirectDependenciesSourceFiles */
final class LocateComposerPackageDirectDependenciesSourceFilesTest extends TestCase
{
    private LocateComposerPackageDirectDependenciesSourceFiles $locator;
    private TemporaryDirectory $root;

    protected function setUp(): void
    {
        parent::setUp();

        $this->locator = new LocateComposerPackageDirectDependenciesSourceFiles();
        $this->root    = (new TemporaryDirectory())
            ->deleteWhenDestroyed()
            ->create();
    }

    public function testNoDependencies(): void
    {
        file_put_contents($this->path('composer.json'), '{}');
        file_put_contents($this->path('vendor/composer/installed.json'), '{"packages":[]}');

        $files = $this->locate($this->path('composer.json'));

        $this->assertCount(0, $files);
    }

    public function testNoInstalledJson(): void
    {
        file_put_contents($this->path('composer.json'), '{}');
        $this->path('vendor/composer');

        $this->expectException(DependenciesNotInstalled::class);
        $this->locate($this->path('composer.json'));
    }

    public function testSingleDependency(): void
    {
        file_put_contents($this->path('composer.json'), '{"require":{"foo/bar": "^1.0"}}');
        file_put_contents($this->path('vendor/composer/installed.json'), '{"packages":[{"name": "foo/bar", "autoload":{"psr-4":{"":"src"}}}]}');
        file_put_contents($this->path('vendor/foo/bar/src/MyClass.php'), '');

        $files = $this->locate($this->path('composer.json'));

        $this->assertCount(1, $files);

        $expectedFile = $this->path('vendor/foo/bar/src/MyClass.php');
        $actualFile   = str_replace(['\\', '/'], DIRECTORY_SEPARATOR, reset($files));
        $this->assertSame($expectedFile, $actualFile);
    }

    public function testVendorDirsWithoutComposerFilesAreIgnored(): void
    {
        file_put_contents($this->path('composer.json'), '{"require": {"foo/bar": "^1.0"}}');
        file_put_contents($this->path('vendor/composer/installed.json'), '{"packages":[]}');
        file_put_contents($this->path('vendor/foo/bar/src/MyClass.php'), '');

        $files = $this->locate($this->path('composer.json'));

        $this->assertCount(0, $files);
    }

    public function testVendorConfigSettingIsBeingUsed(): void
    {
        file_put_contents($this->path('composer.json'), '{"require":{"foo/bar": "^1.0"},"config":{"vendor-dir":"alternate-vendor"}}');
        file_put_contents($this->path('alternate-vendor/composer/installed.json'), '{"packages":[{"name": "foo/bar", "autoload":{"psr-4":{"":"src"}}}]}');
        file_put_contents($this->path('alternate-vendor/foo/bar/src/MyClass.php'), '');

        $files = $this->locate($this->path('composer.json'));

        $this->assertCount(1, $files);

        $expectedFile = $this->path('alternate-vendor/foo/bar/src/MyClass.php');
        $actualFile   = str_replace(['\\', '/'], DIRECTORY_SEPARATOR, reset($files));
        $this->assertSame($expectedFile, $actualFile);
    }

    public function testInstalledJsonUsedAsFallback(): void
    {
        file_put_contents($this->path('composer.json'), '{"require":{"foo/bar": "^1.0"}}');
        file_put_contents($this->path('vendor/composer/installed.json'), '{"packages": [{"name": "foo/bar", "autoload":{"psr-4":{"":"src"}}}]}');
        file_put_contents($this->path('vendor/foo/bar/src/MyClass.php'), '');

        $files = $this->locate($this->path('composer.json'));

        $this->assertCount(1, $files);

        $expectedFile = $this->path('vendor/foo/bar/src/MyClass.php');
        $actualFile   = str_replace(['\\', '/'], DIRECTORY_SEPARATOR, reset($files));
        $this->assertSame($expectedFile, $actualFile);

        // Ensure we didn't leave our temporary composer.json lying around
        $this->assertFalse(file_exists($this->path('vendor/foo/bar/composer.json')));
    }

    /**
     * https://github.com/composer/composer/pull/7999
     */
    public function testOldInstalledJsonUsedAsFallback(): void
    {
        file_put_contents($this->path('composer.json'), '{"require":{"foo/bar": "^1.0"}}');
        file_put_contents($this->path('vendor/composer/installed.json'), '[{"name": "foo/bar", "autoload":{"psr-4":{"":"src"}}}]');
        file_put_contents($this->path('vendor/foo/bar/src/MyClass.php'), '');

        $files = $this->locate($this->path('composer.json'));

        $this->assertCount(1, $files);

        $expectedFile = $this->path('vendor/foo/bar/src/MyClass.php');
        $actualFile   = str_replace(['\\', '/'], DIRECTORY_SEPARATOR, reset($files));
        $this->assertSame($expectedFile, $actualFile);

        // Ensure we didn't leave our temporary composer.json lying around
        $this->assertFalse(file_exists($this->path('vendor/foo/bar/composer.json')));
    }

    /** @return string[] */
    private function locate(string $composerJson): array
    {
        return iterator_to_array(($this->locator)($composerJson));
    }

    private function path(string $path): string
    {
        $path = str_replace('/', DIRECTORY_SEPARATOR, $path);

        return $this->root->path($path);
    }
}
