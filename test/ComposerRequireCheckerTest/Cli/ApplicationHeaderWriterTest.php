<?php

declare(strict_types=1);

namespace ComposerRequireCheckerTest\Cli;

use ComposerRequireChecker\Cli\ApplicationHeaderWriter;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Output\BufferedOutput;

/** @covers \ComposerRequireChecker\Cli\ApplicationHeaderWriter */
final class ApplicationHeaderWriterTest extends TestCase
{
    public function testWithoutApplication(): void
    {
        $output = new BufferedOutput();

        (new ApplicationHeaderWriter())->__invoke($output);

        self::assertStringContainsString('Unknown version', $output->fetch());
    }

    public function testWithApplication(): void
    {
        $output = new BufferedOutput();

        $application = new Application('APPNAME', 'APPVERSION');

        (new ApplicationHeaderWriter($application))->__invoke($output);

        self::assertStringContainsString('APPNAME APPVERSION', $output->fetch());
    }
}
