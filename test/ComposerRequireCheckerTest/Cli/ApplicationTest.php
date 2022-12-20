<?php

declare(strict_types=1);

namespace ComposerRequireCheckerTest\Cli;

use ComposerRequireChecker\Cli\Application;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;

final class ApplicationTest extends TestCase
{
    private Application $application;

    protected function setUp(): void
    {
        $this->application = new Application();
    }

    public function testCheckCommandExists(): void
    {
        $this->assertTrue($this->application->has('check'));
        $this->assertInstanceOf(Command::class, $this->application->get('check'));
    }

    public function testCheckCommandIsDefaultCommand(): void
    {
        self::assertStringContainsString(
            '<info>check</info>',
            $this->application->getDefinition()->getOption('help')->getDescription(),
        );
    }
}
