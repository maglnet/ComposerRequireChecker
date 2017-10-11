<?php

namespace ComposerRequireChecker\DefinedSymbolsLocator;


use ComposerRequireChecker\Exception\UnknownExtensionException;

class LocateDefinedSymbolsFromExtensions
{

    /**
     * @param string[] $extensionNames
     * @return string[]
     * @throws UnknownExtensionException if the extension cannot be found
     */
    public function __invoke(array $extensionNames): array
    {
        $definedSymbols = [];
        foreach ($extensionNames as $extensionName) {
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