<?php

declare(strict_types=1);

namespace ComposerRequireChecker\Cli\ResultsWriter;

use DateTimeImmutable;

use function json_encode;

use const JSON_THROW_ON_ERROR;

final class CliJson implements ResultsWriter
{
    /** @var callable(string): void */
    private $writeCallable;
    private string $applicationVersion;
    /** @var callable(): DateTimeImmutable */
    private $nowCallable;

    /**
     * @param callable(string): void        $write
     * @param callable(): DateTimeImmutable $now
     */
    public function __construct(callable $write, string $applicationVersion, callable $now)
    {
        $this->writeCallable      = $write;
        $this->applicationVersion = $applicationVersion;
        $this->nowCallable        = $now;
    }

    /**
     * {@inheritdoc}
     */
    public function write(array $unknownSymbols): void
    {
        $write = $this->writeCallable;
        $now   = $this->nowCallable;

        $write(
            json_encode(
                [
                    '_meta' => [
                        'composer-require-checker' => [
                            'version' => $this->applicationVersion,
                        ],
                        'date' => $now()->format(DateTimeImmutable::ATOM),
                    ],
                    'unknown-symbols' => $unknownSymbols,
                ],
                JSON_THROW_ON_ERROR
            )
        );
    }
}
