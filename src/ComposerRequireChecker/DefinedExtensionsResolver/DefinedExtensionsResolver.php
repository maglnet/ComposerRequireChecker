<?php

namespace ComposerRequireChecker\DefinedExtensionsResolver;

class DefinedExtensionsResolver
{
    public function __invoke(string $composerJson, array $phpCoreExtensions = []): array
    {
        $composerJsonContent = file_get_contents($composerJson);
        if ($composerJsonContent === false) {
            throw new \InvalidArgumentException('could not load file [' . $composerJson . ']');
        }
        $requires = json_decode($composerJsonContent, true)['require'] ?? [];

        $extensions = [];
        foreach ($requires as $require => $version) {
            if ($require == 'php' || $require == 'php-64bit') {
                $extensions = array_merge($extensions, $phpCoreExtensions);
                continue;
            }
            if (strpos($require, 'ext-') === 0) {
                $extensions = array_merge($extensions, [substr($require, 4)]);
            }
        }
        return $extensions;
    }
}
