<?php

declare(strict_types=1);

namespace ComposerRequireChecker\FileLocator;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;
use Traversable;

use function implode;
use function preg_match;
use function preg_quote;
use function preg_replace;
use function str_replace;
use function trim;

use const DIRECTORY_SEPARATOR;

final class LocateAllFilesByExtension
{
    /**
     * @param Traversable<string> $directories
     * @param array<string>|null  $blacklist
     *
     * @return Traversable<string>
     */
    public function __invoke(Traversable $directories, string $fileExtension, array|null $blacklist): Traversable
    {
        foreach ($directories as $directory) {
            yield from $this->filterFilesByExtension(
                new RecursiveIteratorIterator(
                    new RecursiveDirectoryIterator($directory),
                    RecursiveIteratorIterator::LEAVES_ONLY,
                ),
                $fileExtension,
                $blacklist,
            );
        }
    }

    /**
     * @param Traversable<SplFileInfo> $files
     * @param array<string>|null       $blacklist
     *
     * @return Traversable<string>
     */
    private function filterFilesByExtension(Traversable $files, string $fileExtension, array|null $blacklist): Traversable
    {
        $extensionMatcher = '/.*' . preg_quote($fileExtension) . '$/';

        $blacklistMatcher = '{(' . implode('|', $this->prepareBlacklistPatterns($blacklist)) . ')}';

        foreach ($files as $file) {
            if ($blacklist && preg_match($blacklistMatcher, $file->getPathname())) {
                continue;
            }

            if (! preg_match($extensionMatcher, $file->getBasename())) {
                continue;
            }

            yield $file->getPathname();
        }
    }

    /**
     * @param array<string>|null $blacklistPaths
     *
     * @return array<string>
     */
    private function prepareBlacklistPatterns(array|null $blacklistPaths): array
    {
        if ($blacklistPaths === null) {
            return [];
        }

        $dirSep = preg_quote(DIRECTORY_SEPARATOR, '{}');

        foreach ($blacklistPaths as &$path) {
            $path = preg_replace(
                '{' . $dirSep . '+}',
                DIRECTORY_SEPARATOR,
                preg_quote(
                    trim(str_replace('/', DIRECTORY_SEPARATOR, $path), DIRECTORY_SEPARATOR),
                    '{}',
                ),
            );
            $path = str_replace('\\*\\*', '.+?', $path);
            $path = str_replace('\\*', '[^' . $dirSep . ']+?', $path);
        }

        return $blacklistPaths;
    }
}
