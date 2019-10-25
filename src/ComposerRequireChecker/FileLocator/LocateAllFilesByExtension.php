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

        $blacklist = $this->prepareBlacklistPatterns($blacklist);

        /* @var $file \SplFileInfo */
        foreach ($files as $file) {
            if ($blacklist && preg_match('{('.implode('|', $blacklist).')}', $file->getPathname())) {
                continue;
            }

            if (!preg_match($extensionMatcher, $file->getBasename())) {
                continue;
            }

            yield $file->getPathname();
        }
    }

    private function prepareBlacklistPatterns(?array $blacklistPaths)
    {
        if ($blacklistPaths === null) {
            return $blacklistPaths;
        }

        foreach ($blacklistPaths as &$path) {
            /** @var string $path */
            $path = preg_replace('{/+}', '/', preg_quote(trim(strtr($path, '\\', '/'), '/')));
            $path = str_replace('\\*\\*', '.+?', $path);
            $path = str_replace('\\*', '[^/]+?', $path);
        }

        return $blacklistPaths;
    }
}
