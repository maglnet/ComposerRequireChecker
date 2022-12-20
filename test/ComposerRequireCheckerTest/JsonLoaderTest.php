<?php

declare(strict_types=1);

namespace ComposerRequireCheckerTest;

use ComposerRequireChecker\Exception\InvalidJson;
use ComposerRequireChecker\Exception\NotReadable;
use ComposerRequireChecker\JsonLoader;
use PHPUnit\Framework\TestCase;

/** @covers \ComposerRequireChecker\JsonLoader */
final class JsonLoaderTest extends TestCase
{
    public function testHasErrorWithWrongPath(): void
    {
        $path = __DIR__ . '/wrong/path/non-existing-file.json';
        $this->expectException(NotReadable::class);
        JsonLoader::getData($path);
    }

    public function testHasErrorWithInvalidFile(): void
    {
        $path = __DIR__ . '/../fixtures/invalidJson';
        $this->expectException(InvalidJson::class);
        JsonLoader::getData($path);
    }

    public function testHasDataWithValidFileButNoArrayContent(): void
    {
        $path = __DIR__ . '/../fixtures/validJsonNotAnArray.json';
        $this->expectException(InvalidJson::class);
        JsonLoader::getData($path);
    }

    public function testHasDataWithValidFile(): void
    {
        $path = __DIR__ . '/../fixtures/validJson.json';
        $this->assertEquals(JsonLoader::getData($path), ['foo' => 'bar']);
    }
}
