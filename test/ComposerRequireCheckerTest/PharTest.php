<?php

declare(strict_types=1);

namespace ComposerRequireCheckerTest;

use PHPUnit\Framework\TestCase;

use function chdir;
use function dirname;
use function escapeshellarg;
use function exec;
use function getcwd;
use function implode;
use function realpath;
use function sprintf;

use const DIRECTORY_SEPARATOR;
use const PHP_BINARY;

final class PharTest extends TestCase
{
    private string $bin;
    private string $oldWorkingDirectory;

    protected function setUp(): void
    {
        $phar = realpath(dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'build' . DIRECTORY_SEPARATOR . 'composer-require-checker.phar');

        if ($phar === false) {
            $this->markTestSkipped('Compiled PHAR not found');
        }

        $this->oldWorkingDirectory = getcwd();
        $this->bin                 = PHP_BINARY . ' ' . escapeshellarg($phar);
    }

    protected function tearDown(): void
    {
        if ($this->oldWorkingDirectory === getcwd()) {
            return;
        }

        chdir($this->oldWorkingDirectory);
    }

    public function testVersion(): void
    {
        $command = $this->bin . ' --version';
        exec($command, $output, $return);
        $this->assertStringContainsString('ComposerRequireChecker', implode("\n", $output));
        $this->assertSame(0, $return);
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
        $this->assertStringContainsString('The following 7 unknown symbols were found', implode("\n", $output));
        $this->assertNotEquals(0, $return);
    }

    public function testInvalidConfiguration(): void
    {
        $path = __DIR__ . '/../fixtures/validJson.json';
        exec(sprintf('%s check %s 2>&1', $this->bin, $path), $output, $return);
        $this->assertStringContainsString('dependencies have not been installed', implode("\n", $output));
        $this->assertNotEquals(0, $return);
    }

    public function testDefaultConfiguration(): void
    {
        chdir(__DIR__ . '/../fixtures/defaultConfigPath');
        exec(sprintf('%s check 2>&1', $this->bin), $output, $return);
        $output = implode("\n", $output);

        $this->assertMatchesRegularExpression('/The following [12] unknown symbols were found/', $output);
        $this->assertMatchesRegularExpression('/Composer\\\\InstalledVersions/', $output);
        $this->assertNotEquals(0, $return);
    }

    public function testMissingCustomConfiguration(): void
    {
        chdir(__DIR__ . '/../fixtures/noUnknownComposerSymbol');
        exec(sprintf('%s check --config-file=%s 2>&1', $this->bin, 'no-such-file'), $output, $return);

        $this->assertStringContainsString('Configuration file [no-such-file] does not exist.', implode("\n", $output));
        $this->assertNotEquals(0, $return);
    }

    public function testMissingDefaultConfiguration(): void
    {
        chdir(__DIR__ . '/../fixtures/noUnknownComposerSymbol');
        exec(sprintf('%s check --config-file=%s 2>&1', $this->bin, 'composer-require-checker.json'), $output, $return);

        $this->assertStringContainsString('Configuration file [composer-require-checker.json] does not exist.', implode("\n", $output));
        $this->assertNotEquals(0, $return);
    }
}
