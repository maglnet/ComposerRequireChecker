<?php

namespace ComposerRequireCheckerTest;

use function implode;
use PHPUnit\Framework\TestCase;

final class BinaryTest extends TestCase
{
    /** @var string */
    private $bin;

    protected function setUp(): void
    {
        if (strpos(\PHP_OS, "WIN") === 0) {
            $this->bin = __DIR__ . "\\..\\..\\bin\\composer-require-checker.bat";
        } else {
            $this->bin = __DIR__ . "/../../bin/composer-require-checker";
        }
    }

    public function testSuccess(): void
    {
        exec($this->bin, $output, $return);
        $this->assertSame(0, $return);
    }

    public function testUnknownSymbols(): void
    {
        $path = __DIR__ . "/../fixtures/unknownSymbols/composer.json";
        exec("{$this->bin} check {$path} 2>&1", $output, $return);
        $this->assertSame(1, $return);
        $this->assertStringContainsString("The following 2 unknown symbols were found", implode("\n", $output));
    }

    public function testInvalidConfiguration(): void
    {
        $path = __DIR__ . "/../fixtures/validJson.json";
        exec("{$this->bin} check {$path} 2>&1", $output, $return);
        $this->assertSame(1, $return);
        $this->assertStringContainsString("dependencies have not been installed", implode("\n", $output));
    }
}
