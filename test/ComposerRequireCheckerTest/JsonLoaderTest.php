<?php

declare(strict_types=1);

namespace ComposerRequireCheckerTest;

use ComposerRequireChecker\Exception\InvalidJson;
use ComposerRequireChecker\JsonLoader;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psl\File\Exception\NotFoundException;
use Psl\Type;

#[CoversClass(JsonLoader::class)]
final class JsonLoaderTest extends TestCase
{
    public function testHasErrorWithWrongPath(): void
    {
        $this->expectException(NotFoundException::class);

        JsonLoader::getData(__DIR__ . '/wrong/path/non-existing-file.json', Type\null());
    }

    public function testHasErrorWithInvalidFile(): void
    {
        $path = __DIR__ . '/../fixtures/invalidJson';
        $this->expectException(InvalidJson::class);
        $this->expectExceptionMessage('error parsing ' . $path . ': Syntax error');
        $this->expectExceptionCode(0);

        JsonLoader::getData($path, Type\null());
    }

    public function testHasDataWithValidFileButNoArrayContent(): void
    {
        $path = __DIR__ . '/../fixtures/validJsonNotAnArray.json';
        $this->expectException(InvalidJson::class);
        JsonLoader::getData($path, Type\vec(Type\null()));
    }

    public function testHasDataWithValidComposerFile(): void
    {
        $path = __DIR__ . '/../../composer.json';

        $loaded = JsonLoader::getData($path, JsonLoader::composerDataType());

        self::assertEquals('maglnet/composer-require-checker', $loaded['name'] ?? null);
    }
}
