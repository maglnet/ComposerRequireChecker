<?php declare(strict_types=1);
namespace ComposerRequireCheckerTest;

use ComposerRequireChecker\Exception\InvalidJsonException;
use ComposerRequireChecker\Exception\NotReadableException;
use ComposerRequireChecker\JsonLoader;
use PHPUnit\Framework\TestCase;

/**
 * @covers \ComposerRequireChecker\JsonLoader
 */
class JsonLoaderTest extends TestCase
{

    public function testHasErrorWithWrongPath()
    {
        $path = __DIR__ . '/wrong/path/non-existing-file.json';
        $this->expectException(NotReadableException::class);
        new JsonLoader($path);
    }

    public function testHasErrorWithInvalidFile()
    {
        $path = __DIR__ . '/../fixtures/invalidJson';
        $this->expectException(InvalidJsonException::class);
        new JsonLoader($path);
    }

    public function testHasDataWithValidFile()
    {
        $path = __DIR__ . '/../fixtures/validJson.json';
        $loader = new JsonLoader($path);
        $this->assertEquals($loader->getData(), ['foo' => 'bar']);
    }

}
