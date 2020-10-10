<?php

declare(strict_types=1);

namespace ComposerRequireChecker\FileLocator;

use Traversable;
use Webmozart\Glob\Glob;

use function rtrim;

class LocateFilesByGlobPattern
{
    /**
     * @see    https://github.com/webmozart/glob
     *
     * @param  string[] $globPatterns a list of glob patterns to find files in
     * @param  string   $rootDir      the root directory that should be used when patterns are relative paths
     *
     * @return Traversable<string> the files found by the given glob patterns
     */
    public function __invoke(array $globPatterns, string $rootDir): Traversable
    {
        foreach ($globPatterns as $globPattern) {
            yield from Glob::glob(rtrim($rootDir, '/') . '/' . $globPattern);
        }
    }
}
