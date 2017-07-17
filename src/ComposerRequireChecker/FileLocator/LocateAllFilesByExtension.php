<?php

namespace ComposerRequireChecker\FileLocator;

use Traversable;

final class LocateAllFilesByExtension
{
    public function __invoke(Traversable $directories, string $fileExtension) : Traversable
    {
        foreach ($directories as $directory) {
            yield from $this->filterFilesByExtension(
                new \RecursiveIteratorIterator(
                    new \RecursiveDirectoryIterator($directory),
                    \RecursiveIteratorIterator::LEAVES_ONLY
                ),
                $fileExtension
            );
        }
    }

    private function filterFilesByExtension(Traversable $files, string $fileExtension) : Traversable
    {
        $extensionMatcher = '/.*' . preg_quote($fileExtension) . '$/';

        /* @var $file \SplFileInfo */
        foreach ($files as $file) {
            if ($file->isDir() || ! preg_match($extensionMatcher, $file->getBasename())) {
                continue;
            }

            yield $file->getPathname();
        }
    }
}
