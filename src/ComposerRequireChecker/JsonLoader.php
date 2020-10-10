<?php

declare(strict_types=1);

namespace ComposerRequireChecker;

use ComposerRequireChecker\Exception\InvalidJsonException;
use ComposerRequireChecker\Exception\NotReadableException;

use function file_get_contents;
use function is_readable;
use function json_decode;
use function json_last_error;
use function json_last_error_msg;

use const JSON_ERROR_NONE;

class JsonLoader
{
    /** @var mixed */
    private $data;

    /**
     * @internal
     *
     * @throws   InvalidJsonException
     * @throws   NotReadableException
     */
    public function __construct(string $path)
    {
        if (! is_readable($path) || ($content = file_get_contents($path)) === false) {
            throw new NotReadableException('unable to read ' . $path);
        }

        $this->data = json_decode($content, true);
        if ($this->data === null && json_last_error() !== JSON_ERROR_NONE) {
            throw new InvalidJsonException('error parsing ' . $path . ': ' . json_last_error_msg());
        }
    }

    /**
     * @internal
     *
     * @return   mixed
     */
    public function getData()
    {
        return $this->data;
    }
}
