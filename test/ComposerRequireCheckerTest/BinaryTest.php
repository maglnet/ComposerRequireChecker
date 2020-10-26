<?php

declare(strict_types=1);

namespace ComposerRequireCheckerTest;

use PHPUnit\Framework\TestCase;

use function exec;
use function implode;
use function sprintf;
use function strpos;

use const PHP_OS;

final class BinaryTest extends TestCase
{
    private string $bin;

    protected function setUp(): void
    {
        if (strpos(PHP_OS, 'WIN') === 0) {
            $this->bin = __DIR__ . "\\..\\..\\bin\\composer-require-checker.bat";
        } else {
            $this->bin = __DIR__ . '/../../bin/composer-require-checker';
        }
    }

    public function testSuccess(): void
    {
        exec($this->bin, $output, $return);
        $this->assertSame(0, $return);
    }

    public function testUnknownSymbols(): void
    {
        $path = __DIR__ . '/../fixtures/unknownSymbols/composer.json';
        exec(sprintf('%s check %s 2>&1', $this->bin, $path), $output, $return);
        $this->assertSame(1, $return);
        $this->assertStringContainsString('The following 6 unknown symbols were found', implode("\n", $output));
    }

    public function testInvalidConfiguration(): void
    {
        $path = __DIR__ . '/../fixtures/validJson.json';
        exec(sprintf('%s check %s 2>&1', $this->bin, $path), $output, $return);
        $this->assertSame(1, $return);
        $this->assertStringContainsString('dependencies have not been installed', implode("\n", $output));
    }
}
