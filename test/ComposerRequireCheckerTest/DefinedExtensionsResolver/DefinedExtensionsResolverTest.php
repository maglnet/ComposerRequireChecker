<?php

declare(strict_types=1);

namespace ComposerRequireCheckerTest\DefinedExtensionsResolver;

use ComposerRequireChecker\DefinedExtensionsResolver\DefinedExtensionsResolver;
use PHPUnit\Framework\TestCase;
use Spatie\TemporaryDirectory\TemporaryDirectory;

use function file_put_contents;

/** @covers \ComposerRequireChecker\DefinedExtensionsResolver\DefinedExtensionsResolver */
final class DefinedExtensionsResolverTest extends TestCase
{
    private DefinedExtensionsResolver $resolver;
    private TemporaryDirectory $root;

    protected function setUp(): void
    {
        parent::setUp();

        $this->resolver = new DefinedExtensionsResolver();

        $this->root = (new TemporaryDirectory())
            ->deleteWhenDestroyed()
            ->create();
    }

    public function testNoExtensions(): void
    {
        $composerJson = $this->createComposerJson('{}');

        $extensions = ($this->resolver)($composerJson);

        $this->assertCount(0, $extensions);
    }

    public function testCoreExtensions(): void
    {
        $composerJson = $this->createComposerJson('{"require":{"php":"^7.0"}}');

        $extensions = ($this->resolver)($composerJson, ['foo']);

        $this->assertCount(1, $extensions);
        $this->assertContains('foo', $extensions);
    }

    public function testCoreExtensionsIn64Bit(): void
    {
        $composerJson = $this->createComposerJson('{"require":{"php-64bit":"^7.0"}}');

        $extensions = ($this->resolver)($composerJson, ['foo']);

        $this->assertCount(1, $extensions);
        $this->assertContains('foo', $extensions);
    }

    public function testExtensionsAreReturned(): void
    {
        $composerJson = $this->createComposerJson('{"require":{"ext-zip":"*","ext-curl":"*"}}');

        $extensions = ($this->resolver)($composerJson, ['foo']);

        $this->assertCount(2, $extensions);
        $this->assertContains('zip', $extensions);
        $this->assertContains('curl', $extensions);
        $this->assertNotContains('foo', $extensions);
    }

    public function testExtensionsAreAddedWhenBothCoreAndExtensionsRequired(): void
    {
        $composerJson = $this->createComposerJson('{"require":{"php":"~8.2.0","ext-zip":"*","ext-curl":"*"}}');

        $extensions = ($this->resolver)($composerJson, ['foo']);

        $this->assertCount(3, $extensions);
        $this->assertContains('foo', $extensions);
        $this->assertContains('curl', $extensions);
        $this->assertContains('zip', $extensions);
    }

    public function testExtensionsFoundWhenAfterOtherPackages(): void
    {
        $composerJson = $this->createComposerJson(
            '{"require":{"maglnet/composer-require-checker":"*","php":"~8.2.0","ext-zip":"*","ext-curl":"*"}}',
        );

        $extensions = ($this->resolver)($composerJson, ['foo']);

        $this->assertCount(3, $extensions);
        $this->assertContains('foo', $extensions);
        $this->assertContains('curl', $extensions);
        $this->assertContains('zip', $extensions);
    }

    private function createComposerJson(string $content): string
    {
        $fullPath = $this->root->path('composer.json');
        file_put_contents($fullPath, $content);

        return $fullPath;
    }
}
