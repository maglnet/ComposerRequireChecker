<?php

declare(strict_types=1);

namespace ComposerRequireCheckerTest\Cli;

use ComposerRequireChecker\Cli\Options;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Psl\File;
use Psl\Json;
use Psl\Type;

final class OptionsTest extends TestCase
{
    public function testOptionsAcceptPhpCoreExtensions(): void
    {
        $options = new Options([
            'php-core-extensions' => ['something'],
        ]);

        $this->assertSame(['something'], $options->getPhpCoreExtensions());
    }

    public function testOptionsAcceptSymbolWhitelistAndFiltersDuplicates(): void
    {
        $options = new Options([
            'symbol-whitelist' => ['foo', 'bar', 'null'],
        ]);

        $this->assertSame([
            'null',
            'true',
            'false',
            'static',
            'self',
            'parent',
            'array',
            'string',
            'int',
            'float',
            'bool',
            'iterable',
            'callable',
            'void',
            'object',
            'mixed',
            'never',
            'AllowDynamicProperties',
            'Attribute',
            'Override',
            'ReturnTypeWillChange',
            'SensitiveParameter',
            'foo',
            'bar',
        ], $options->getSymbolWhitelist());
    }

    public function testOptionsFileRepresentsDefaults(): void
    {
        $this->assertEquals(
            new Options(),
            new Options(Json\typed(
                File\read(__DIR__ . '/../../../data/config.dist.json'),
                Type\dict(Type\string(), Type\mixed()),
            )),
        );
    }

    public function testThrowsExceptionForUnknownOptions(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('foo-bar is not a known option - there is no method setFooBar');

        new Options([
            'foo-bar' => ['foo', 'bar'],
        ]);
    }

    public function testPublicSetters(): void
    {
        $options = new Options();

        $options->setSymbolWhitelist(['foo', 'bar']);
        $options->setScanFiles(['one', 'two', 'three']);
        $options->setPhpCoreExtensions(['ext-one', 'ext-two']);

        $this->assertSame([
            'null',
            'true',
            'false',
            'static',
            'self',
            'parent',
            'array',
            'string',
            'int',
            'float',
            'bool',
            'iterable',
            'callable',
            'void',
            'object',
            'mixed',
            'never',
            'AllowDynamicProperties',
            'Attribute',
            'Override',
            'ReturnTypeWillChange',
            'SensitiveParameter',
            'foo',
            'bar',
        ], $options->getSymbolWhitelist());

        self::assertSame(['one', 'two', 'three'], $options->getScanFiles());
        self::assertSame(['ext-one', 'ext-two'], $options->getPhpCoreExtensions());
    }
}
