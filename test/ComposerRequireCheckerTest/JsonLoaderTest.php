<?php

namespace ComposerRequireCheckerTest;

use ComposerRequireChecker\Exception\InvalidJsonException;
use ComposerRequireChecker\Exception\NotReadableException;
use ComposerRequireChecker\JsonLoader;
use PHPUnit\Framework\TestCase;

/**
 * @covers \ComposerRequireChecker\JsonLoader
 */
final class JsonLoaderTest extends TestCase
{
    public function testHasErrorWithWrongPath(): void
    {
        $path = __DIR__ . '/wrong/path/non-existing-file.json';
        $this->expectException(NotReadableException::class);
        new JsonLoader($path);
    }

    public function testHasErrorWithInvalidFile(): void
    {
        $path = __DIR__ . '/../fixtures/invalidJson';
        $this->expectException(InvalidJsonException::class);
        new JsonLoader($path);
    }

    public function testHasDataWithValidFile(): void
    {
        $path = __DIR__ . '/../fixtures/validJson.json';
        $loader = new JsonLoader($path);
        $this->assertEquals($loader->getData(), ['foo' => 'bar']);
    }
}
