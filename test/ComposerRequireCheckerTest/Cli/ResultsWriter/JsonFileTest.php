<?php

declare(strict_types=1);

namespace ComposerRequireCheckerTest\Cli\ResultsWriter;

use ComposerRequireChecker\Cli\ResultsWriter\JsonFile;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use PHPUnit\Framework\TestCase;

use function file_get_contents;
use function json_decode;

final class JsonFileTest extends TestCase
{
    private JsonFile $writer;
    private vfsStreamDirectory $root;

    protected function setUp(): void
    {
        parent::setUp();

        $this->root   = vfsStream::setup();
        $this->writer = new JsonFile('vfs://root/path/name.json', '0.0.1');
    }

    public function testWriteReport(): void
    {
        $this->writer->write([
            'Foo' => [],
            'opcache_get_status' => ['ext-opcache'],
            'dummy' => ['ext-dummy', 'ext-other'],
        ]);

        self::assertFileExists('vfs://root/path/name.json');

        $actual                  = json_decode(file_get_contents('vfs://root/path/name.json'), true);
        $actual['_meta']['date'] = 'ATOM_DATE';

        self::assertSame(
            [
                '_meta' => [
                    'composer-require-checker' => ['version' => '0.0.1'],
                    'date' => 'ATOM_DATE',
                ],
                'unknown-symbols' => [
                    'Foo' => [],
                    'opcache_get_status' => ['ext-opcache'],
                    'dummy' => ['ext-dummy', 'ext-other'],
                ],
            ],
            $actual
        );
    }
}
