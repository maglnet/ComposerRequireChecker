<?php

namespace ComposerRequireCheckerTest\DefinedExtensionsResolver;

use ComposerRequireChecker\DefinedExtensionsResolver\DefinedExtensionsResolver;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use PHPUnit\Framework\TestCase;

class DefinedExtensionsResolverTest extends TestCase
{
    /** @var DefinedExtensionsResolver */
    private $resolver;
    /** @var vfsStreamDirectory */
    private $root;

    protected function setUp()
    {
        parent::setUp();

        $this->resolver = new DefinedExtensionsResolver();
        $this->root = vfsStream::setup();
    }

    public function testNoExtensions()
    {
        $composerJson = vfsStream::newFile('composer.json')->at($this->root)->setContent('{}')->url();

        $extensions = ($this->resolver)($composerJson);

        $this->assertCount(0, $extensions);
    }

    public function testCoreExtensions()
    {
        $composerJson = vfsStream::newFile('composer.json')->at($this->root)
            ->setContent('{"require":{"php":"^7.0"}}')
            ->url();

        $extensions = ($this->resolver)($composerJson, ['ext-foo' => '*']);

        $this->assertCount(1, $extensions);
        $this->assertSame('*', reset($extensions));
    }

    public function testExtensionsAreReturned()
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
