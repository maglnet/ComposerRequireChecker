<?php

namespace ComposerRequireChecker\FileLocator;

use Traversable;

final class LocateAllFilesByExtension
{
    public function __invoke(Traversable $directories, string $fileExtension, ?array $blacklist): Traversable
    {
        foreach ($directories as $directory) {
            yield from $this->filterFilesByExtension(
                new \RecursiveIteratorIterator(
                    new \RecursiveDirectoryIterator($directory),
                    \RecursiveIteratorIterator::LEAVES_ONLY
                ),
                $fileExtension,
                $blacklist
            );
        }
    }

    private function filterFilesByExtension(Traversable $files, string $fileExtension, ?array $blacklist): Traversable
    {
        $extensionMatcher = '/.*' . preg_quote($fileExtension) . '$/';

        $blacklistMatcher = '{('.implode('|', $this->prepareBlacklistPatterns($blacklist)).')}';

        /* @var $file \SplFileInfo */
        foreach ($files as $file) {
            if ($blacklist && preg_match($blacklistMatcher, $this->normalizeFilepath($file->getPathname()))) {
                continue;
            }

            if (!preg_match($extensionMatcher, $file->getBasename())) {
                continue;
            }

            yield $file->getPathname();
        }
    }

    private function prepareBlacklistPatterns(?array $blacklistPaths): array
    {
        if ($blacklistPaths === null) {
            return [];
        }

        $dirSep = \preg_quote(DIRECTORY_SEPARATOR, '{}');

        foreach ($blacklistPaths as &$path) {
            $path = preg_replace('{' . $dirSep . '+}', DIRECTORY_SEPARATOR, \preg_quote(trim(str_replace('/', DIRECTORY_SEPARATOR, $path), DIRECTORY_SEPARATOR), '{}'));
            $path = str_replace('\\*\\*', '.+?', $path);
            $path = str_replace('\\*', '[^' . $dirSep . ']+?', $path);
        }

        return $blacklistPaths;
    }
    
    private function normalizeFilepath(string $filepath) {
        return str_replace('\\', '/', $filepath);
    }
}
