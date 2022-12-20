<?php

declare(strict_types=1);

namespace ComposerRequireChecker\DefinedSymbolsLocator;

use ComposerRequireChecker\Exception\UnknownExtension;
use ReflectionExtension;
use Throwable;

use function array_keys;
use function array_merge;

use const PHP_VERSION_ID;

class LocateDefinedSymbolsFromExtensions
{
    /**
     * @see   https://github.com/maglnet/ComposerRequireChecker/issues/99
     *
     * @const string composer does some interpolation on the package name of extensions: str_replace(' ', '-', $name)
     *        which means that composer.json must have 'ext-zend-opcache' instead of the correct / exact package name
     *        which is 'ext-Zend Opcache'. So the ALTERNATIVES allows us to look up the correct name, case sensitive
     *        without the '-'.
     */
    private const ALTERNATIVES = ['zend-opcache' => 'Zend Opcache'];

    /**
     * @param  string[] $extensionNames
     *
     * @return string[]
     *
     * @throws UnknownExtension if the extension cannot be found.
     */
    public function __invoke(array $extensionNames): array
    {
        $definedSymbols = [];
        foreach ($extensionNames as $extensionName) {
            $extensionName = self::ALTERNATIVES[$extensionName] ?? $extensionName;

            // @infection-ignore-all LessThan No point in testing this on 8.2.0 specifically
            if ($extensionName === 'random' && PHP_VERSION_ID < 80200) {
                continue;
            }

            try {
                $extensionReflection = new ReflectionExtension($extensionName);
                $definedSymbols      = array_merge(
                    $definedSymbols,
                    array_keys($extensionReflection->getConstants()),
                    array_keys($extensionReflection->getFunctions()),
                    $extensionReflection->getClassNames(),
                );
            } catch (Throwable $e) {
                throw new UnknownExtension($e->getMessage());
            }
        }

        return $definedSymbols;
    }
}
