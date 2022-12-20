<?php

declare(strict_types=1);

namespace ComposerRequireCheckerTest\Cli\ResultsWriter;

use ComposerRequireChecker\Cli\ResultsWriter\CliJson;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

use function json_decode;

use const JSON_THROW_ON_ERROR;

final class CliJsonTest extends TestCase
{
    private CliJson $writer;
    private string $output = '';

    protected function setUp(): void
    {
        parent::setUp();

        $this->writer = new CliJson(
            function (string $string): void {
                $this->output .= $string;
            },
            '0.0.1',
            static fn () => new DateTimeImmutable('@0')
        );
    }

    public function testWriteReport(): void
    {
        $this->writer->write([
            'Foo' => [],
            'opcache_get_status' => ['ext-opcache'],
            'dummy' => ['ext-dummy', 'ext-other'],
        ]);

        $actual = json_decode($this->output, true, JSON_THROW_ON_ERROR);

        self::assertSame(
            [
                '_meta' => [
                    'composer-require-checker' => ['version' => '0.0.1'],
                    'date' => '1970-01-01T00:00:00+00:00',
                ],
                'unknown-symbols' => [
                    'Foo' => [],
                    'opcache_get_status' => ['ext-opcache'],
                    'dummy' => ['ext-dummy', 'ext-other'],
                ],
            ],
            $actual,
        );
    }
}
