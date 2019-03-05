<?php

namespace ComposerRequireCheckerTest;

use function implode;
use PHPUnit\Framework\TestCase;

class BinaryTest extends TestCase
{
    /** @var string */
    private $bin;

    public function setUp(): void
    {
        $this->bin = __DIR__ . "/../../bin/composer-require-checker";
    }

    public function testSuccess()
    {
        exec($this->bin, $output, $return);
        $this->assertSame(0, $return);
    }

    public function testUnknownSymbols()
    {
        $path = __DIR__ . "/../fixtures/unknownSymbols/composer.json";
        exec("{$this->bin} check {$path} 2>&1", $output, $return);
        $this->assertSame(1, $return);
        $this->assertContains("The following unknown symbols were found", implode("\n", $output));
    }

    public function testInvalidConfiguration()
    {
        $path = __DIR__ . "/../fixtures/validJson.json";
        exec("{$this->bin} check {$path} 2>&1", $output, $return);
        $this->assertSame(1, $return);
        $this->assertContains("dependencies have not been installed", implode("\n", $output));
    }
}
