<?php

namespace ComposerRequireCheckerTest\FileLocator;

use ComposerRequireChecker\FileLocator\LocateComposerPackageDirectDependenciesSourceFiles;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use PHPUnit\Framework\TestCase;

/**
 * @covers \ComposerRequireChecker\FileLocator\LocateComposerPackageDirectDependenciesSourceFiles
 */
final class LocateComposerPackageDirectDependenciesSourceFilesTest extends TestCase
{
    /** @var LocateComposerPackageDirectDependenciesSourceFiles */
    private $locator;
    /** @var vfsStreamDirectory */
    private $root;

    protected function setUp(): void
    {
        parent::setUp();

        $this->locator = new LocateComposerPackageDirectDependenciesSourceFiles();
        $this->root = vfsStream::setup();
    }

    public function testNoDependencies(): void
    {
        vfsStream::create([
            'composer.json' => '{}',
            'vendor' => [
                'composer' => [
                    'installed.json' => '{"packages":[]}',
                ],
            ],
        ]);

        $files = $this->locate($this->root->getChild('composer.json')->url(), 'require');

        $this->assertCount(0, $files);
    }

    public function testSingleDependency(): void
    {
        vfsStream::create([
            'composer.json' => '{"require":{"foo/bar": "^1.0"}}',
            'vendor' => [
                'composer' => [
                    'installed.json' => '{"packages":[{"name": "foo/bar", "autoload":{"psr-4":{"":"src"}}}]}',
                ],
                'foo' => [
                    'bar' => [
                        'src' => [
                            'MyClass.php' => '',
                        ],
                    ],
                ],
            ],
        ]);

        $files = $this->locate($this->root->getChild('composer.json')->url(), 'require');

        $this->assertCount(1, $files);

        $expectedFile = $this->root->getChild('vendor/foo/bar/src/MyClass.php')->url();
        $actualFile = str_replace('\\', '/', reset($files));
        $this->assertSame($expectedFile, $actualFile);
    }

    public function testVendorDirsWithoutComposerFilesAreIgnored(): void
    {
        vfsStream::create([
            'composer.json' => '{"require": {"foo/bar": "^1.0"}}',
            'vendor' => [
                'composer' => [
                    'installed.json' => '{"packages":[]}',
                ],
                'foo' => [
                    'bar' => [
                        'src' => [
                            'MyClass.php' => '',
                        ],
                    ],
                ],
            ],
        ]);

        $files = $this->locate($this->root->getChild('composer.json')->url(), 'require');

        $this->assertCount(0, $files);
    }

    public function testVendorConfigSettingIsBeingUsed(): void
    {
        vfsStream::create([
            'composer.json' => '{"require":{"foo/bar": "^1.0"},"config":{"vendor-dir":"alternate-vendor"}}',
            'alternate-vendor' => [
                'composer' => [
                    'installed.json' => '{"packages":[{"name": "foo/bar", "autoload":{"psr-4":{"":"src"}}}]}',
                ],
                'foo' => [
                    'bar' => [
                        'src' => [
                            'MyClass.php' => '',
                        ],
                    ],
                ],
            ],
        ]);

        $files = $this->locate($this->root->getChild('composer.json')->url(), 'require');

        $this->assertCount(1, $files);

        $expectedFile = $this->root->getChild('alternate-vendor/foo/bar/src/MyClass.php')->url();
        $actualFile = str_replace('\\', '/', reset($files));
        $this->assertSame($expectedFile, $actualFile);
    }

    public function testInstalledJsonUsedAsFallback(): void
    {
        vfsStream::create([
            'composer.json' => '{"require":{"foo/bar": "^1.0"}}',
            'vendor' => [
                'composer' => [
                    'installed.json' => '{"packages": [{"name": "foo/bar", "autoload":{"psr-4":{"":"src"}}}]}',
                ],
                'foo' => [
                    'bar' => [
                        'src' => [
                            'MyClass.php' => '',
                        ],
                    ],
                ],
            ],
        ]);

        $files = $this->locate($this->root->getChild('composer.json')->url(), 'require');

        $this->assertCount(1, $files);

        $expectedFile = $this->root->getChild('vendor/foo/bar/src/MyClass.php')->url();
        $actualFile = str_replace('\\', '/', reset($files));
        $this->assertSame($expectedFile, $actualFile);

        # Ensure we didn't leave our temporary composer.json lying around
        $this->assertFalse($this->root->hasChild('vendor/foo/bar/composer.json'));
    }


    /**
     * https://github.com/composer/composer/pull/7999
     */
    public function testOldInstalledJsonUsedAsFallback(): void
    {
        vfsStream::create([
            'composer.json' => '{"require":{"foo/bar": "^1.0"}}',
            'vendor' => [
                'composer' => [
                    'installed.json' => '[{"name": "foo/bar", "autoload":{"psr-4":{"":"src"}}}]',
                ],
                'foo' => [
                    'bar' => [
                        'src' => [
                            'MyClass.php' => '',
                        ],
                    ],
                ],
            ],
        ]);

        $files = $this->locate($this->root->getChild('composer.json')->url(), 'require');

        $this->assertCount(1, $files);

        $expectedFile = $this->root->getChild('vendor/foo/bar/src/MyClass.php')->url();
        $actualFile = str_replace('\\', '/', reset($files));
        $this->assertSame($expectedFile, $actualFile);

        # Ensure we didn't leave our temporary composer.json lying around
        $this->assertFalse($this->root->hasChild('vendor/foo/bar/composer.json'));
    }

    public function testWithoutDevDependencies(): void
    {
        vfsStream::create([
            'composer.json' => '{"require":{"foo/bar": "^1.0"},"require-dev":{"foo/baz": "^1.0"}}',
            'vendor' => [
                'composer' => [
                    'installed.json' =>
                        '{"packages":[' .
                            '{"name": "foo/bar", "autoload":{"psr-4":{"":"src"}}},' .
                            '{"name": "foo/baz", "autoload":{"psr-4":{"":"src"}}}' .
                        ']}',
                ],
                'foo' => [
                    'bar' => [
                        'src' => [
                            'MyClass.php' => '',
                        ],
                    ],
                    'baz' => [
                        'src' => [
                            'BazClass.php' => '',
                        ],
                    ],
                ],
            ],
        ]);

        $files = $this->locate($this->root->getChild('composer.json')->url(), 'require');

        $this->assertCount(1, $files);

        $expectedFile = $this->root->getChild('vendor/foo/bar/src/MyClass.php')->url();
        $actualFile = str_replace('\\', '/', reset($files));
        $this->assertSame($expectedFile, $actualFile);
    }

    public function testWithDevDependencies(): void
    {
        vfsStream::create([
            'composer.json' => '{"require":{"foo/bar": "^1.0"},"require-dev":{"foo/baz": "^1.0"}}',
            'vendor' => [
                'composer' => [
                    'installed.json' => <<<'INSTALLEDJSON'
{
    "packages": [
        {"name": "foo/bar", "autoload": {"psr-4":{"":"src"}}},
        {"name": "foo/baz", "autoload": {"psr-4":{"":"src"}}}
    ]
}
INSTALLEDJSON
                ],
                'foo' => [
                    'bar' => [
                        'src' => [
                            'MyClass.php' => '',
                        ],
                    ],
                    'baz' => [
                        'src' => [
                            'BazClass.php' => '',
                        ],
                    ],
                ],
            ],
        ]);

        $files = $this->locate($this->root->getChild('composer.json')->url(), 'require-dev');

        $this->assertCount(1, $files);
        $this->assertContains($this->root->getChild('vendor/foo/baz/src/BazClass.php')->url(), $files);
    }

    /**
     * @param string $composerJson
     * @param string $requireKey
     * @return string[]
     */
    private function locate(string $composerJson, string $requireKey): array
    {
        $files = [];
        $generator = ($this->locator)($composerJson, $requireKey);
        foreach ($generator as $file) {
            $files[] = str_replace(DIRECTORY_SEPARATOR, '/', $file);
        }
        return $files;
    }
}
