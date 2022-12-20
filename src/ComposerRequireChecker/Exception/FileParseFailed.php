<?php

declare(strict_types=1);

namespace ComposerRequireChecker\Exception;

use RuntimeException;
use Throwable;

use function sprintf;

class FileParseFailed extends RuntimeException
{
    public function __construct(string $file, Throwable|null $previous = null)
    {
        $msg = sprintf(
            'Parsing the file [%s] resulted in an error: %s',
            $file,
            $previous?->getMessage() ?? '',
        );

        parent::__construct($msg, 0, $previous);
    }
}
