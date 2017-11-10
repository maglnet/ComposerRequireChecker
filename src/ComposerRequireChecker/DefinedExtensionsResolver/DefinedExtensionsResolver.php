<?php declare(strict_types=1);
namespace ComposerRequireChecker\DefinedExtensionsResolver;

class DefinedExtensionsResolver
{

    public function __invoke(string $composerJson, array $phpCoreExtensions = []): array
    {
        $requires = json_decode(file_get_contents($composerJson), true)['require'] ?? [];

        $extensions = [];
        foreach ($requires as $require => $version) {
            if ($require == 'php') {
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
