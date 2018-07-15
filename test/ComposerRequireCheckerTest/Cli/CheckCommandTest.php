<?php

namespace ComposerRequireCheckerTest\Cli;

use ComposerRequireChecker\Cli\Application;
use ComposerRequireChecker\Cli\CheckCommand;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;
use Symfony\Component\Console\Formatter\OutputFormatterInterface;
use Symfony\Component\Console\Output\OutputInterface;
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
        $this->markTestSkipped();
        self::expectException(\InvalidArgumentException::class);

        $this->commandTester->execute([
            'composer-json' => 'this-will-not-be-found.json'
        ]);
    }

    public function testSelfCheckShowsNoErrors()
    {
        $this->markTestSkipped();
        $this->commandTester->execute([
            // that's our own composer.json, lets be sure our self check does not throw errors
            'composer-json' => dirname(__DIR__, 3) . '/composer.json'
        ]);

        $this->assertSame(0, $this->commandTester->getStatusCode());
        $this->assertContains('no unknown symbols found', $this->commandTester->getDisplay());
    }

    /**
     * @test
     * @return void
     */
    public function testHandleResultForFailure(): void
    {
        $command = new CheckCommand();
        $method = new ReflectionMethod($command, 'handleResult');
        $method->setAccessible(true);
        $output = $this->getMockBuilder(OutputInterface::class)->getMock();
        $printed = [];
        $output->expects($this->any())
            ->method('writeln')
            ->willReturnCallback(function ($line) use (&$printed) {
                 if ($line) {
                    $printed[] = $line;
                 }
             });
        $output->expects($this->any())
            ->method('getFormatter')
            ->with()
            ->willReturn($this->getMockBuilder(OutputFormatterInterface::class)->getMock());
        $this->assertEquals(1, $method->invoke(
            $command,
            ['A_Symbol', 'A\\Nother\\Symbol', 'A_Third\\Symbol'],
            $output
        ));
        $this->assertEquals(
            [
                "The following unknown symbols were found:",
                "+--+--+",
                "|<info> unknown symbol </info>|<info> guessed dependency </info>|",
                "+--+--+",
                "| A_Symbol |  |",
                "| A\\Nother\\Symbol |  |",
                "| A_Third\\Symbol |  |",
                "+--+--+"
            ],
            $printed
        );
    }
}
