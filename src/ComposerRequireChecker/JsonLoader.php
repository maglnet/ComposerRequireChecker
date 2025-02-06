<?php

declare(strict_types=1);

namespace ComposerRequireChecker;

use ComposerRequireChecker\Exception\InvalidJson;
use Psl\File;
use Psl\Json;
use Psl\Type;
use Psl\Type\TypeInterface;

/**
 * @internal
 *
 * @psalm-type ComposerConfig = array{vendor-dir?: string}
 * @psalm-type ComposerAutoload = array{
 *                 exclude-from-classmap?: list<string>,
 *                 classmap?: list<string>,
 *                 files?: list<string>,
 *                 psr-0?: array<string, string|list<string>>,
 *                 psr-4?: array<string, string|list<string>>
 *             }
 * @psalm-type ComposerPackageData = array{
 *                 name: string,
 *                 require?: array<string, string>,
 *                 autoload?: ComposerAutoload,
 *                 config?: ComposerConfig
 *             }
 * @psalm-type InstalledComposerPackageData = array{
 *                  name: string,
 *                  require?: array<string, string>,
 *                  autoload?: ComposerAutoload
 *              }
 * @psalm-type ComposerData = array{
 *                 name?: string,
 *                 require?: array<string, string>,
 *                 autoload?: ComposerAutoload,
 *                 config?: ComposerConfig,
 *                 packages?: list<ComposerPackageData>
 *             }
 * @psalm-type InstalledComposerData = array{
 *                 packages?: list<InstalledComposerPackageData>
 *             }
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
     * @return T of mixed
     *
     * @throws InvalidJson
     * @throws File\Exception\NotFoundException
     *
     * @template T
     */
    public static function getData(string $path, TypeInterface $type): mixed
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

    /** @return Type\TypeInterface<InstalledComposerData> */
    public static function installedDataType(): Type\TypeInterface
    {
        $autoload = Type\shape([
            'exclude-from-classmap' => Type\optional(Type\vec(Type\string())),
            'classmap' => Type\optional(Type\vec(Type\string())),
            'files' => Type\optional(Type\vec(Type\string())),
            'psr-0' => Type\optional(Type\dict(
                Type\string(),
                Type\union(Type\string(), Type\vec(Type\string())),
            )),
            'psr-4' => Type\optional(Type\dict(
                Type\string(),
                Type\union(Type\string(), Type\vec(Type\string())),
            )),
        ], true);

        $package = Type\shape([
            'name' => Type\string(),
            'require' => Type\optional(Type\dict(Type\string(), Type\string())),
            'autoload' => Type\optional($autoload),
        ]);

        return Type\shape([
            'packages' => Type\optional(Type\vec($package)),
        ], true);
    }

    /** @return Type\TypeInterface<ComposerData> */
    public static function composerDataType(): Type\TypeInterface
    {
        $composerConfig = Type\shape([
            'vendor-dir' => Type\optional(Type\string()),
        ], true);

        $autoload = Type\shape([
            'exclude-from-classmap' => Type\optional(Type\vec(Type\string())),
            'classmap' => Type\optional(Type\vec(Type\string())),
            'files' => Type\optional(Type\vec(Type\string())),
            'psr-0' => Type\optional(Type\dict(
                Type\string(),
                Type\union(Type\string(), Type\vec(Type\string())),
            )),
            'psr-4' => Type\optional(Type\dict(
                Type\string(),
                Type\union(Type\string(), Type\vec(Type\string())),
            )),
        ], true);

        $package = Type\shape([
            'name' => Type\string(),
            'require' => Type\optional(Type\dict(Type\string(), Type\string())),
            'autoload' => Type\optional($autoload),
            'config' => Type\optional($composerConfig),
        ]);

        return Type\shape([
            'name' => Type\optional(Type\string()),
            'require' => Type\optional(Type\dict(Type\string(), Type\string())),
            'autoload' => Type\optional($autoload),
            'config' => Type\optional($composerConfig),
            'packages' => Type\optional(Type\vec($package)),
        ], true);
    }
}
