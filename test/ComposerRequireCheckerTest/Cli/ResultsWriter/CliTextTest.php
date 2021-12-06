<?php

declare(strict_types=1);

namespace ComposerRequireCheckerTest\Cli\ResultsWriter;

use ComposerRequireChecker\Cli\ResultsWriter\CliText;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Output\BufferedOutput;

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
}
