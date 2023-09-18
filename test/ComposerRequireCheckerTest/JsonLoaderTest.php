<?php

declare(strict_types=1);

namespace ComposerRequireCheckerTest;

use ComposerRequireChecker\Exception\InvalidJson;
use ComposerRequireChecker\Exception\NotReadable;
use ComposerRequireChecker\JsonLoader;
use PHPUnit\Framework\TestCase;

use function is_readable;

use const PHP_OS_FAMILY;

/** @covers \ComposerRequireChecker\JsonLoader */
final class JsonLoaderTest extends TestCase
{
    public function testHasErrorWithWrongPath(): void
    {
        $path = __DIR__ . '/wrong/path/non-existing-file.json';
        $this->expectException(NotReadable::class);
        $this->expectExceptionMessage('unable to read file: The file "' . $path . '" does not exist.');
        $this->expectExceptionCode(0);
        JsonLoader::getData($path);
    }

    public function testHasErrorWithInvalidFile(): void
    {
        $path = __DIR__ . '/../fixtures/invalidJson';
        $this->expectException(InvalidJson::class);
        $this->expectExceptionMessage('error parsing ' . $path . ': Syntax error');
        $this->expectExceptionCode(0);
        JsonLoader::getData($path);
    }

    public function testHasErrorWithUnreadableFile(): void
    {
        $path = '/etc/shadow';
        if (PHP_OS_FAMILY !== 'Linux' || is_readable($path)) {
            $this->markTestSkipped('This test relies on ' . $path . ' existing, but being unreadable.');
        }

        $this->expectException(NotReadable::class);
        $this->expectExceptionMessage('unable to read file: The path "' . $path . '" is not readable.');
        $this->expectExceptionCode(0);
        JsonLoader::getData($path);
    }

    public function testHasErrorWithDirectory(): void
    {
        $path = __DIR__;
        $this->expectException(NotReadable::class);
        $this->expectExceptionMessage('unable to read file: The path "' . $path . '" is not a file.');
        $this->expectExceptionCode(0);
        JsonLoader::getData($path);
    }

    public function testHasDataWithValidFileButNoArrayContent(): void
    {
        $path = __DIR__ . '/../fixtures/validJsonNotAnArray.json';
        $this->expectException(InvalidJson::class);
        $this->expectExceptionMessage('error parsing ' . $path . ': Expected an array.');
        $this->expectExceptionCode(0);
        JsonLoader::getData($path);
    }

    public function testHasDataWithValidFileButExcessiveDepth(): void
    {
        $path = __DIR__ . '/../fixtures/validJsonExcessiveDepth.json';
        $this->expectException(InvalidJson::class);
        $this->expectExceptionMessage('error parsing ' . $path . ': Maximum stack depth exceeded');
        $this->expectExceptionCode(0);
        JsonLoader::getData($path);
    }

    public function testHasDataWithValidFileWithVeryLargeDepth(): void
    {
        $path = __DIR__ . '/../fixtures/validJsonVeryLargeDepth.json';
        $data = JsonLoader::getData($path);
        $this->assertEquals('bar', $data['foo'] ?? null);
    }

    public function testHasDataWithValidFile(): void
    {
        $path = __DIR__ . '/../fixtures/validJson.json';
        $this->assertEquals(JsonLoader::getData($path), ['foo' => 'bar']);
    }
}
