<?php
/**
 * Created by PhpStorm.
 * User: matthias
 * Date: 01.12.15
 * Time: 22:10
 */

namespace ComposerRequireCheckerTest\Cli;

use ComposerRequireChecker\Cli\Options;
use PHPUnit\Framework\TestCase;

class OptionsTest extends TestCase
{
    public function testOptionsAcceptPhpCoreExtensions()
    {
        $options = new Options([
            'php-core-extensions' => ['something']
        ]);

        $this->assertSame(['something'], $options->getPhpCoreExtensions());
    }

    public function testOptionsAppendPhpCoreExtensions()
    {
        $options = new Options([
            'append' => [
                'php-core-extensions' => ['something']
            ],
        ]);

        $this->assertContains('something', $options->getPhpCoreExtensions());
        $this->assertContains('Reflection', $options->getPhpCoreExtensions());
    }

    public function testOptionsAcceptSymbolWhitelist()
    {
        $options = new Options([
            'symbol-whitelist' => ['foo', 'bar']
        ]);

        $this->assertSame(['foo', 'bar'], $options->getSymbolWhitelist());
    }

    public function testOptionsAppendSymbolWhitelist()
    {
        $options = new Options([
            'append' => [
                'symbol-whitelist' => ['foo', 'bar'],
            ],
        ]);

        $this->assertContains('foo', $options->getSymbolWhitelist());
        $this->assertContains('bar', $options->getSymbolWhitelist());
        $this->assertContains('null', $options->getSymbolWhitelist());
    }

    public function testOptionsFileRepresentsDefaults()
    {
        $options = new Options();

        $optionsFromFile = new Options(
            json_decode(file_get_contents(
                __DIR__ . '/../../../data/config.dist.json'
            ), true)
        );

        $this->assertEquals($options, $optionsFromFile);
    }

    public function testThrowsExceptionForUnknownOptions()
    {
        $this->expectException('InvalidArgumentException');
        $options = new Options([
            'foo-bar' => ['foo', 'bar']
        ]);
    }
}
