<?php

namespace ComposerRequireCheckerTest\Cli;

use ComposerRequireChecker\Cli\Application;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Tester\CommandTester;

final class CheckCommandTest extends TestCase
{

    /**
     * @var CommandTester
     */
    private $commandTester;

    protected function setUp(): void
    {
        $application = new Application();
        $command = $application->get('check');

        $this->commandTester = new CommandTester($command);
    }

    public function testExceptionIfComposerJsonNotFound(): void
    {
        self::expectException(\InvalidArgumentException::class);

        $this->commandTester->execute([
            'composer-json' => 'this-will-not-be-found.json'
        ]);
    }

    public function testSelfCheckShowsNoErrors(): void
    {
        $this->commandTester->execute([
            // that's our own composer.json, lets be sure our self check does not throw errors
            'composer-json' => dirname(__DIR__, 3) . '/composer.json'
        ]);

        $this->assertSame(0, $this->commandTester->getStatusCode());
        $this->assertStringContainsString('no unknown symbols found', $this->commandTester->getDisplay());

        // verbose output should not be shown
        $this->assertNotRegExp('/Collecting defined (vendor|extension) symbols... found \d+ symbols./', $this->commandTester->getDisplay());
        $this->assertNotRegExp('/Collecting used symbols... found \d+ symbols./', $this->commandTester->getDisplay());
    }

    public function testSelfCheckIncludingDevShowsNoErrors(): void
    {
        $this->commandTester->execute([
            // that's our own composer.json, lets be sure our self check does not throw errors
            'composer-json' => dirname(__DIR__, 3) . '/composer.json'
        ], [
            'include-dev' => true
        ]);

        $this->assertSame(0, $this->commandTester->getStatusCode());
        $this->assertStringContainsString('no unknown symbols found', $this->commandTester->getDisplay());

        // verbose output should not be shown
        $this->assertNotRegExp('/Collecting defined (vendor|extension) symbols... found \d+ symbols./', $this->commandTester->getDisplay());
        $this->assertNotRegExp('/Collecting used symbols... found \d+ symbols./', $this->commandTester->getDisplay());
    }

    public function testVerboseSelfCheckShowsCounts(): void
    {
        $this->commandTester->execute([
            // that's our own composer.json
            'composer-json' => dirname(__DIR__, 3) . '/composer.json',
        ], [
            'verbosity' => OutputInterface::VERBOSITY_VERBOSE,
        ]);

        $this->assertRegExp('/Collecting defined vendor symbols... found \d+ symbols./', $this->commandTester->getDisplay());
        $this->assertRegExp('/Collecting defined extension symbols... found \d+ symbols./', $this->commandTester->getDisplay());
        $this->assertRegExp('/Collecting used symbols... found \d+ symbols./', $this->commandTester->getDisplay());
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

        $this->assertRegExp('/There were no unknown symbols found./', $this->commandTester->getDisplay());
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
        $this->assertRegExp('/The following unknown symbols were found.*PHPUnit\\\\Framework\\\\TestCase/s', $this->commandTester->getDisplay());
    }
}
