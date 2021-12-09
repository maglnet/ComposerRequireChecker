<?php

declare(strict_types=1);

namespace ComposerRequireChecker;

use ComposerRequireChecker\Exception\InvalidJson;
use ComposerRequireChecker\Exception\NotReadable;
use ComposerRequireChecker\FileLocator\LocateComposerPackageSourceFiles;
use InvalidArgumentException;
use Throwable;
use Webmozart\Assert\Assert;

use function assert;
use function file_get_contents;
use function is_string;
use function json_decode;

use const JSON_THROW_ON_ERROR;

/**
 * @internal
 *
 * @psalm-import-type ComposerData from LocateComposerPackageSourceFiles
 */
class JsonLoader
{
    /**
     * @return array<array-key, mixed>
     * @psalm-return ComposerData
     *
     * @throws InvalidJson
     * @throws NotReadable
     */
    public static function getData(string $path): array
    {
        $content = self::getFileContentFromPath($path);

        try {
            $decodedData = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
            Assert::isArray($decodedData);
        } catch (Throwable $exception) {
            throw new InvalidJson('error parsing ' . $path . ': ' . $exception->getMessage(), 0, $exception);
        }

        /** @psalm-var ComposerData $decodedData */
        return $decodedData;
    }

    private static function getFileContentFromPath(string $path): string
    {
        try {
            Assert::file($path);
            Assert::readable($path);
        } catch (InvalidArgumentException $exception) {
            throw new NotReadable('unable to read ' . $path, 0, $exception);
        }

        $content = file_get_contents($path);

        assert(is_string($content));

        return $content;
    }
}
