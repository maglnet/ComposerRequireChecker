<?php

declare(strict_types=1);

namespace ComposerRequireChecker\Cli\ResultsWriter;

use DateTimeImmutable;
use RuntimeException;

use function dirname;
use function file_put_contents;
use function function_exists;
use function is_dir;
use function json_encode;
use function mkdir;
use function sprintf;

final class JsonFile implements ResultsWriter
{
    private string $filePathname;
    private string $applicationVersion;

    public function __construct(string $filePathname, string $applicationVersion)
    {
        if (! function_exists('json_encode')) {
            throw new RuntimeException('Missing extension: json');
        }

        $this->filePathname       = $filePathname;
        $this->applicationVersion = $applicationVersion;
    }

    /**
     * {@inheritdoc}
     */
    public function write(array $unknownSymbols): void
    {
        $filePath = dirname($this->filePathname);
        if (! mkdir($filePath, 0777, true) && ! is_dir($filePath)) {
            throw new RuntimeException(sprintf('Directory "%s" was not created', $filePath));
        }

        file_put_contents(
            $this->filePathname,
            json_encode(
                [
                    '_meta' => [
                        'composer-require-checker' => [
                            'version' => $this->applicationVersion,
                        ],
                        'date' => (new DateTimeImmutable())->format(DateTimeImmutable::ATOM),
                    ],
                    'unknown-symbols' => $unknownSymbols,
                ],
            )
        );
    }
}
