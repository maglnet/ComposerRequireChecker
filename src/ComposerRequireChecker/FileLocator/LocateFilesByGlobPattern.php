<?php

namespace ComposerRequireChecker\FileLocator;


use Webmozart\Glob\Glob;

class LocateFilesByGlobPattern
{

    public function __invoke(array $globPatterns = [], $rootDir) : \Generator
    {
        $files = [];

        foreach ($globPatterns as $globPattern) {
            $files = Glob::glob(rtrim($rootDir, '/') . '/' . $globPattern);
        }


        yield from $files;
    }

}
