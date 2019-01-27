<?php

namespace ComposerRequireChecker\DefinedSymbolsLocator;

use ComposerRequireChecker\Exception\UnknownExtensionException;

class LocateDefinedSymbolsFromExtensions
{
    /**
     * @const string composer does some interpolation on the package name of extensions: str_replace(' ', '-', $name)
     *        which means that composer.json must have 'ext-zend-opcache' instead of the correct / exact package name
     *        which is 'ext-Zend Opcache'. So the ALTERNATIVES allows us to look up the correct name, case sensitive
     *        without the '-'.
     * @see https://github.com/maglnet/ComposerRequireChecker/issues/99
     */
    private const ALTERNATIVES = ['zend-opcache' => 'Zend Opcache'];

    /**
     * @param string[] $extensionNames
     * @return string[]
     * @throws UnknownExtensionException if the extension cannot be found
     */
    public function __invoke(array $extensionNames): array
    {
        $definedSymbols = [];
        foreach ($extensionNames as $extensionName) {
            $extensionName = self::ALTERNATIVES[$extensionName] ?? $extensionName;
            try {
                $extensionReflection = new \ReflectionExtension($extensionName);
                $definedSymbols = array_merge(
                    $definedSymbols,
                    array_keys($extensionReflection->getConstants()),
                    array_keys($extensionReflection->getFunctions()),
                    $extensionReflection->getClassNames()
                );
            } catch (\Exception $e) {
                throw new UnknownExtensionException($e->getMessage());
            }
        }
        return $definedSymbols;
    }
}
