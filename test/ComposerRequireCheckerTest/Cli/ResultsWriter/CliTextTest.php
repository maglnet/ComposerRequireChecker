<?php

declare(strict_types=1);

namespace ComposerRequireCheckerTest\Cli\ResultsWriter;

use ComposerRequireChecker\Cli\ResultsWriter\CliText;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\OutputInterface;

use const PHP_EOL;

final class CliTextTest extends TestCase
{
    private CliText $writer;
    private BufferedOutput $output;

    protected function setUp(): void
    {
        parent::setUp();

        $this->output = new BufferedOutput();
        $this->writer = new CliText($this->output);
    }

    public function testWriteReportNoUnknownSymbolsFound(): void
    {
        $this->writer->write([]);

        self::assertSame('There were no unknown symbols found.' . PHP_EOL, $this->output->fetch());
    }

    public function testWriteReportWithUnknownSymbols(): void
    {
        $this->writer->write([
            'Foo' => [],
            'opcache_get_status' => ['ext-opcache'],
            'dummy' => ['ext-dummy', 'ext-other'],
        ]);

        $buffer = $this->output->fetch();
        self::assertStringContainsString('The following 3 unknown symbols were found:', $buffer);
        self::assertStringContainsString('Foo', $buffer);
        self::assertStringContainsString('| opcache_get_status', $buffer);
        self::assertStringContainsString('| ext-opcache', $buffer);
        self::assertStringContainsString('| dummy', $buffer);
        self::assertStringContainsString('| ext-dummy', $buffer);
        self::assertStringContainsString('| ext-other', $buffer);
    }

    public function testWriteReportQuiet(): void
    {
        $this->output->setVerbosity(OutputInterface::VERBOSITY_QUIET);

        $this->writer->write([
            'Foo' => [],
            'opcache_get_status' => ['ext-opcache'],
            'dummy' => ['ext-dummy', 'ext-other'],
        ]);

        $buffer = $this->output->fetch();
        self::assertSame('', $buffer);
    }

    public function testWriteReportQuietWithWriteCallable(): void
    {
        $output = '';
        $write  = static function (string $string) use (&$output): void {
            $output .= $string;
        };

        $writer = new CliText($this->output, $write);
        $writer->write([
            'Foo' => [],
            'opcache_get_status' => ['ext-opcache'],
            'dummy' => ['ext-dummy', 'ext-other'],
        ]);

        $buffer = $this->output->fetch();
        self::assertStringContainsString('The following 3 unknown symbols were found:', $buffer);
        self::assertStringNotContainsString('Foo', $buffer);
        self::assertStringContainsString('Unknown Symbol', $output);
        self::assertStringContainsString('Guessed Dependency', $output);
        self::assertStringContainsString('Foo', $output);
        self::assertStringContainsString('| opcache_get_status', $output);
        self::assertStringContainsString('| ext-opcache', $output);
        self::assertStringContainsString('| dummy', $output);
        self::assertStringContainsString('| ext-dummy', $output);
        self::assertStringContainsString('| ext-other', $output);
    }
}
