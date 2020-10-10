<?php

declare(strict_types=1);

namespace ComposerRequireCheckerTest\Cli;

use ComposerRequireChecker\Cli\Application;
use InvalidArgumentException;
use LogicException;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Tester\CommandTester;

use function dirname;
use function file_put_contents;
use function unlink;
use function version_compare;

use const PHP_VERSION;

final class CheckCommandTest extends TestCase
{
    private CommandTester $commandTester;

    protected function setUp(): void
    {
        $application = new Application();
        $command     = $application->get('check');

        $this->commandTester = new CommandTester($command);
    }

    public function testExceptionIfComposerJsonNotFound(): void
    {
        self::expectException(InvalidArgumentException::class);

        $this->commandTester->execute(['composer-json' => 'this-will-not-be-found.json']);
    }

    public function testExceptionIfNoSymbolsFound(): void
    {
        self::expectException(LogicException::class);

        $this->commandTester->execute([
            'composer-json' => dirname(__DIR__, 2) . '/fixtures/noSymbols/composer.json',
        ]);
    }

    public function testUnknownSymbolsFound(): void
    {
        $this->commandTester->execute([
            'composer-json' => dirname(__DIR__, 2) . '/fixtures/unknownSymbols/composer.json',
        ]);

        $this->assertSame(1, $this->commandTester->getStatusCode());
        $this->assertStringContainsString('The following unknown symbols were found:', $this->commandTester->getDisplay());
        $this->assertStringContainsString('Doctrine\Common\Collections\ArrayCollection', $this->commandTester->getDisplay());
        $this->assertStringContainsString('Example\Library\Dependency', $this->commandTester->getDisplay());
        $this->assertStringContainsString('FILTER_VALIDATE_URL', $this->commandTester->getDisplay());
        $this->assertStringContainsString('filter_var', $this->commandTester->getDisplay());
        $this->assertStringContainsString('Foo\Bar\Baz', $this->commandTester->getDisplay());
        $this->assertStringContainsString('libxml_clear_errors', $this->commandTester->getDisplay());
    }

    public function testSelfCheckShowsNoErrors(): void
    {
        $this->commandTester->execute([
            // that's our own composer.json, lets be sure our self check does not throw errors
            'composer-json' => dirname(__DIR__, 3) . '/composer.json',
        ]);

        $this->assertSame(0, $this->commandTester->getStatusCode());
        $this->assertStringContainsString('no unknown symbols found', $this->commandTester->getDisplay());

        // verbose output should not be shown
        $this->assertDoesNotMatchRegularExpression('/Collecting defined (vendor|extension) symbols... found \d+ symbols./', $this->commandTester->getDisplay());
        $this->assertDoesNotMatchRegularExpression('/Collecting used symbols... found \d+ symbols./', $this->commandTester->getDisplay());
    }

    public function testVerboseSelfCheckShowsCounts(): void
    {
        $this->commandTester->execute([
            // that's our own composer.json
            'composer-json' => dirname(__DIR__, 3) . '/composer.json',
        ], [
            'verbosity' => OutputInterface::VERBOSITY_VERBOSE,
        ]);

        $this->assertMatchesRegularExpression('/Collecting defined vendor symbols... found \d+ symbols./', $this->commandTester->getDisplay());
        $this->assertMatchesRegularExpression('/Collecting defined extension symbols... found \d+ symbols./', $this->commandTester->getDisplay());
        $this->assertMatchesRegularExpression('/Collecting used symbols... found \d+ symbols./', $this->commandTester->getDisplay());
    }

    public function testWithAdditionalSourceFiles(): void
    {
        $root = vfsStream::setup();
        vfsStream::create([
            'config.json' => <<<JSON
{
    "scan-files": ["src/ComposerRequireChecker/Cli/CheckCommand.php"]
}
JSON
            ,
        ]);

        $this->commandTester->execute([
            // that's our own composer.json
            'composer-json' => dirname(__DIR__, 3) . '/composer.json',
            '--config-file' => $root->getChild('config.json')->url(),
        ]);

        $this->assertMatchesRegularExpression('/There were no unknown symbols found./', $this->commandTester->getDisplay());
    }

    public function testSourceFileThatUsesDevDependency(): void
    {
        $root = vfsStream::setup();
        vfsStream::create(['config.json' => '{"scan-files":["test/ComposerRequireCheckerTest/Cli/CheckCommandTest.php"]}']);

        $exitCode = $this->commandTester->execute([
            // that's our own composer.json
            'composer-json' => dirname(__DIR__, 3) . '/composer.json',
            '--config-file' => $root->getChild('config.json')->url(),
        ]);

        $this->assertNotEquals(0, $exitCode);
        $this->assertMatchesRegularExpression('/The following unknown symbols were found.*PHPUnit\\\\Framework\\\\TestCase/s', $this->commandTester->getDisplay());
    }

    public function testNoUnknownSymbolsFound(): void
    {
        $baseDir = dirname(__DIR__, 2) . '/fixtures/noUnknownSymbols/';
        $this->commandTester->execute(['composer-json' => $baseDir . 'composer.json']);

        self::assertSame(Command::SUCCESS, $this->commandTester->getStatusCode());
        self::assertStringContainsString('There were no unknown symbols found.', $this->commandTester->getDisplay());
    }

    public function testReservedKeywordInPhp8DoesNotThrowExceptionInPhp7(): void
    {
        if (version_compare(PHP_VERSION, '8.0.0') >= 0) {
            self::markTestSkipped('This test does not work in PHP8');
        }

        $baseDir = dirname(__DIR__, 2) . '/fixtures/noUnknownSymbols/';
        $tmpFile = $baseDir . 'src/Match.php';
        file_put_contents($tmpFile, '<?php class Match { }');

        $this->commandTester->execute(['composer-json' => $baseDir . 'composer.json']);

        unlink($tmpFile);
        self::assertSame(Command::SUCCESS, $this->commandTester->getStatusCode());
    }
}
