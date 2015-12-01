<?php
/**
 * Created by PhpStorm.
 * User: matthias
 * Date: 01.12.15
 * Time: 22:10
 */

namespace ComposerRequireCheckerTest\Cli;


use ComposerRequireChecker\Cli\Options;

class OptionsTest extends \PHPUnit_Framework_TestCase
{


    public function testOptionsAcceptPhpCoreExtensions()
    {
        $options = new Options([
            'php-core-extensions' => ['something']
        ]);

        $this->assertSame(['something'], $options->getPhpCoreExtensions());
    }

    public function testOptionsAcceptSymbolWhitelist()
    {
        $options = new Options([
            'symbol-whitelist' => ['foo', 'bar']
        ]);

        $this->assertSame(['foo', 'bar'], $options->getSymbolWhitelist());
    }

    public function testThrowsExceptionForUnknownOptions()
    {
        $this->setExpectedException('InvalidArgumentException');
        $options = new Options([
            'foo-bar' => ['foo', 'bar']
        ]);

    }

}