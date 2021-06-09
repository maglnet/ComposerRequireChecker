<?php

declare(strict_types=1);

namespace ComposerRequireChecker\Exception;

use RuntimeException;
use Throwable;

class FileParseFailed extends RuntimeException
{
    public function __construct(string $file, ?Throwable $previous = null)
    {
        parent::__construct($file . ': ' . $previous->getMessage(), 0, $previous);
    }
}
