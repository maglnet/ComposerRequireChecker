<?php

namespace ComposerRequireCheckerTest\Cli;

use ComposerRequireChecker\Cli\Application;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

class CheckCommandTest extends TestCase
{

    /**
     * @var CommandTester
     */
    private $commandTester;

    public function setUp()
    {
        $application = new Application();
        $command = $application->get('check');

        $this->commandTester = new CommandTester($command);
    }

    public function testExceptionIfComposerJsonNotFound()
    {
        self::expectException(InvalidArgumentException::class);

        $this->commandTester->execute([
            'composer-json' => 'this-will-not-be-found.json'
        ]);
    }

    public function testSelfCheckShowsNoErrors()
    {
        $this->commandTester->execute([
            // that's our own composer.json, lets be sure our self check does not throw errors
            'composer-json' => dirname(__DIR__, 3) . '/composer.json'
        ]);

        $this->assertSame(0, $this->commandTester->getStatusCode());
        $this->assertContains('no unknown symbols found', $this->commandTester->getDisplay());
    }
}
