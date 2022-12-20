<?php

declare(strict_types=1);

namespace ComposerRequireCheckerTest\DefinedExtensionsResolver;

use ComposerRequireChecker\DefinedExtensionsResolver\DefinedExtensionsResolver;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use PHPUnit\Framework\TestCase;

use function reset;

/** @covers \ComposerRequireChecker\DefinedExtensionsResolver\DefinedExtensionsResolver */
final class DefinedExtensionsResolverTest extends TestCase
{
    private DefinedExtensionsResolver $resolver;
    private vfsStreamDirectory $root;

    protected function setUp(): void
    {
        parent::setUp();

        $this->resolver = new DefinedExtensionsResolver();
        $this->root     = vfsStream::setup();
    }

    public function testNoExtensions(): void
    {
        $composerJson = vfsStream::newFile('composer.json')->at($this->root)->setContent('{}')->url();

        $extensions = ($this->resolver)($composerJson);

        $this->assertCount(0, $extensions);
    }

    public function testCoreExtensions(): void
    {
        $composerJson = vfsStream::newFile('composer.json')->at($this->root)
            ->setContent('{"require":{"php":"^7.0"}}')
            ->url();

        $extensions = ($this->resolver)($composerJson, ['ext-foo' => '*']);

        $this->assertCount(1, $extensions);
        $this->assertSame('*', reset($extensions));
    }

    public function testCoreExtensionsIn64Bit(): void
    {
        $composerJson = vfsStream::newFile('composer.json')->at($this->root)
            ->setContent('{"require":{"php-64bit":"^7.0"}}')
            ->url();

        $extensions = ($this->resolver)($composerJson, ['ext-foo' => '*']);

        $this->assertCount(1, $extensions);
        $this->assertSame('*', reset($extensions));
    }

    public function testExtensionsAreReturned(): void
    {
        $composerJson = vfsStream::newFile('composer.json')->at($this->root)
            ->setContent('{"require":{"ext-zip":"*","ext-curl":"*"}}')
            ->url();

        $extensions = ($this->resolver)($composerJson);

        $this->assertCount(2, $extensions);
        $this->assertContains('zip', $extensions);
        $this->assertContains('curl', $extensions);
    }
}
