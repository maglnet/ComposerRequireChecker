<?php

declare(strict_types=1);

namespace ComposerRequireChecker;

use ComposerRequireChecker\Exception\InvalidJson;
use ComposerRequireChecker\Exception\NotReadable;
use Throwable;

use function file_get_contents;
use function is_readable;
use function json_decode;

use const JSON_THROW_ON_ERROR;

/**
 * @internal
 */
class JsonLoader
{
    /**
     * @return array<array-key, mixed>
     *
     * @throws InvalidJson
     * @throws NotReadable
     */
    public static function getData(string $path): array
    {
        if (! is_readable($path)) {
            throw new NotReadable('unable to read ' . $path);
        }

        $content = file_get_contents($path);

        if ($content === false) {
            throw new NotReadable('unable to read ' . $path);
        }

        try {
            $decodedData = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
        } catch (Throwable $exception) {
            throw new InvalidJson('error parsing ' . $path . ': ' . $exception->getMessage(), 0, $exception);
        }

        return $decodedData;
    }
}
