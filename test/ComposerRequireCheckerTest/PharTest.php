<?php

declare(strict_types=1);

namespace ComposerRequireCheckerTest;

use PHPUnit\Framework\Attributes\BeforeClass;
use PHPUnit\Framework\TestCase;
use RuntimeException;

use function chdir;
use function dirname;
use function exec;
use function getcwd;
use function implode;
use function ob_clean;
use function ob_end_flush;
use function ob_start;
use function sprintf;

use const DIRECTORY_SEPARATOR;
use const PHP_BINARY;

final class PharTest extends TestCase
{
    private static string $bin;
    private string $oldWorkingDirectory;

    #[BeforeClass]
    public static function compilePhar(): void
    {
        $doExec           = static function ($command): void {
            ob_start();
            exec($command . ' 2>&1', $output, $return);
            if ($return !== 0) {
                ob_end_flush();

                throw new RuntimeException('Command `' . $command . '` failed with exit code ' . $return . "\n" . implode("\n", $output));
            }

            ob_clean();
        };
        $workingDirectory = getcwd();
        chdir(dirname(__DIR__, 2));
        $doExec('composer --no-interaction install --no-progress --no-suggest');
        $doExec(PHP_BINARY . ' -d phar.readonly=0 vendor/bin/phing phar-build');
        $doExec('composer --no-interaction install --no-progress --no-suggest');
        self::$bin = PHP_BINARY . ' ' . getcwd() . DIRECTORY_SEPARATOR . 'build' . DIRECTORY_SEPARATOR . 'composer-require-checker.phar';
        chdir($workingDirectory);
    }

    protected function setUp(): void
    {
        $this->oldWorkingDirectory = getcwd();
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
        $command = self::$bin . ' --version';
        exec($command, $output, $return);
        $this->assertStringContainsString('ComposerRequireChecker', implode("\n", $output));
        $this->assertSame(0, $return);
    }

    public function testSuccess(): void
    {
        exec(self::$bin, $output, $return);
        $this->assertSame(0, $return);
    }

    public function testUnknownSymbols(): void
    {
        $path = __DIR__ . '/../fixtures/unknownSymbols/composer.json';
        exec(sprintf('%s check %s 2>&1', self::$bin, $path), $output, $return);
        $this->assertStringContainsString('The following 6 unknown symbols were found', implode("\n", $output));
        $this->assertNotEquals(0, $return);
    }

    public function testInvalidConfiguration(): void
    {
        $path = __DIR__ . '/../fixtures/validJson.json';
        exec(sprintf('%s check %s 2>&1', self::$bin, $path), $output, $return);
        $this->assertStringContainsString('dependencies have not been installed', implode("\n", $output));
        $this->assertNotEquals(0, $return);
    }

    public function testDefaultConfiguration(): void
    {
        chdir(__DIR__ . '/../fixtures/defaultConfigPath');
        exec(sprintf('%s check 2>&1', self::$bin), $output, $return);
        $output = implode("\n", $output);

        $this->assertMatchesRegularExpression('/The following [12] unknown symbols were found/', $output);
        $this->assertMatchesRegularExpression('/Composer\\\\InstalledVersions/', $output);
        $this->assertNotEquals(0, $return);
    }

    public function testMissingCustomConfiguration(): void
    {
        chdir(__DIR__ . '/../fixtures/noUnknownComposerSymbol');
        exec(sprintf('%s check --config-file=%s 2>&1', self::$bin, 'no-such-file'), $output, $return);

        $this->assertStringContainsString('Configuration file [no-such-file] does not exist.', implode("\n", $output));
        $this->assertNotEquals(0, $return);
    }
}
