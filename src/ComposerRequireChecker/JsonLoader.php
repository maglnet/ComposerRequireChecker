<?php

declare(strict_types=1);

namespace ComposerRequireChecker;

use ComposerRequireChecker\Exception\InvalidJson;
use ComposerRequireChecker\FileLocator\LocateComposerPackageSourceFiles;
use Psl\File;
use Psl\Json;
use Psl\Type\TypeInterface;

/**
 * @internal
 *
 * @psalm-import-type ComposerData from LocateComposerPackageSourceFiles
 *
 * @psalm-internal ComposerRequireCheckerTest
 * @psalm-internal ComposerRequireChecker
 */
class JsonLoader
{
    /**
     * @param non-empty-string $path
     * @param TypeInterface<T> $type
     *
     * @return T
     *
     * @throws InvalidJson
     * @throws File\Exception\NotFoundException
     *
     * @template T
     */
    public static function getData(string $path, TypeInterface $type): array
    {
        try {
            return Json\typed(
                File\read($path),
                $type,
            );
        } catch (Json\Exception\DecodeException $previous) {
            throw new InvalidJson('error parsing ' . $path . ': ' . $previous->getMessage(), 0, $previous);
        }
    }
}
