<?php

namespace ComposerRequireCheckerTest;

use ComposerRequireChecker\JsonLoader;
use PHPUnit\Framework\TestCase;

class JsonLoaderTest extends TestCase
{

    public function testHasErrorWithWrongPath()
    {
        $path = __DIR__ . '/wrong/path/non-existing-file.json';
        $loader = new JsonLoader($path);
        $this->assertEquals($loader->getErrorCode(), JsonLoader::ERROR_NO_READABLE);
        $this->assertEquals($loader->getPath(), $path);
        $this->assertNull($loader->getData());
    }

    public function testHasErrorWithInvalidFile()
    {
        $path = __DIR__ . '/../fixtures/invalidJson';
        $loader = new JsonLoader($path);
        $this->assertEquals($loader->getErrorCode(), JsonLoader::ERROR_INVALID_JSON);
        $this->assertEquals($loader->getPath(), $path);
        $this->assertNotEmpty($loader->getErrorMessage());
        $this->assertNull($loader->getData());
    }

    public function testHasDataWithValidFile()
    {
        $path = __DIR__ . '/../fixtures/validJson.json';
        $loader = new JsonLoader($path);
        $this->assertEquals($loader->getErrorCode(), JsonLoader::NO_ERROR);
        $this->assertEquals($loader->getPath(), $path);
        $this->assertEmpty($loader->getErrorMessage());
        $this->assertEquals($loader->getData(), ['foo' => 'bar']);
    }

}
