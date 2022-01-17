<?php

declare(strict_types=1);

namespace ComposerRequireChecker\DependencyGuesser;

use ComposerRequireChecker\JsonLoader;
use Generator;
use Roave\BetterReflection\BetterReflection;
use Roave\BetterReflection\Identifier\Identifier;
use Roave\BetterReflection\Identifier\IdentifierType;
use Roave\BetterReflection\Reflection\ReflectionClass;
use Roave\BetterReflection\Reflection\ReflectionConstant;
use Roave\BetterReflection\Reflection\ReflectionFunction;
use Roave\BetterReflection\Reflector\DefaultReflector;
use Roave\BetterReflection\SourceLocator\Type\Composer\Factory\MakeLocatorForInstalledJson;
use Roave\BetterReflection\SourceLocator\Type\MemoizingSourceLocator;
use Roave\BetterReflection\SourceLocator\Type\SourceLocator;

use function preg_match;
use function preg_quote;
use function sprintf;
use function str_replace;

use const DIRECTORY_SEPARATOR;

final class GuessFromInstalledComposerPackages implements Guesser
{
    private SourceLocator $sourceLocator;
    private string $pathRegex;

    public function __construct(string $installationPath)
    {
        $this->sourceLocator = new MemoizingSourceLocator((new MakeLocatorForInstalledJson())(
            $installationPath,
            (new BetterReflection())->astLocator()
        ));

        $cleanPath       = preg_quote(sprintf('%s/vendor', str_replace(DIRECTORY_SEPARATOR, '/', $installationPath)), '@');
        $this->pathRegex = sprintf('@^%s/(?:composer/\.\./)?([^/]+/[^/]+)/@', $cleanPath);
    }

    /**
     * @return Generator<string>
     */
    public function __invoke(string $symbolName): Generator
    {
        foreach ($this->locateIdentifier($symbolName) as $reflection) {
            $path = $reflection->getFileName();

            if ($path === null) {
                continue;
            }

            $matched = preg_match($this->pathRegex, $path, $captures);

            if (! $matched) {
                continue;
            }

            yield JsonLoader::getData($captures[0] . 'composer.json')['name'];
        }
    }

    /**
     * @return Generator<ReflectionClass|ReflectionFunction|ReflectionConstant>
     */
    private function locateIdentifier(string $symbolName): Generator
    {
        $locatedIndentifiers = [
            $this->sourceLocator->locateIdentifier(
                new DefaultReflector($this->sourceLocator),
                new Identifier($symbolName, new IdentifierType(IdentifierType::IDENTIFIER_CLASS))
            ),
            $this->sourceLocator->locateIdentifier(
                new DefaultReflector($this->sourceLocator),
                new Identifier($symbolName, new IdentifierType(IdentifierType::IDENTIFIER_FUNCTION))
            ),
            $this->sourceLocator->locateIdentifier(
                new DefaultReflector($this->sourceLocator),
                new Identifier($symbolName, new IdentifierType(IdentifierType::IDENTIFIER_CONSTANT))
            ),
        ];

        foreach ($locatedIndentifiers as $locatedIndentifier) {
            if (! ($locatedIndentifier instanceof ReflectionFunction || $locatedIndentifier instanceof ReflectionClass || $locatedIndentifier instanceof ReflectionConstant)) {
                continue;
            }

            yield $locatedIndentifier;
        }
    }
}
