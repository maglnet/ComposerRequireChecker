<?php

declare(strict_types=1);

namespace ComposerRequireCheckerTest\Cli;

use ComposerRequireChecker\Cli\ApplicationHeaderWriter;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\Output;

#[CoversClass(ApplicationHeaderWriter::class)]
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

    public function testQuiet(): void
    {
        $output = new BufferedOutput();

        $output->setVerbosity(Output::VERBOSITY_QUIET);

        $application = new Application('APPNAME', 'APPVERSION');

        (new ApplicationHeaderWriter($application))->__invoke($output);

        self::assertEmpty($output->fetch());
    }
}
