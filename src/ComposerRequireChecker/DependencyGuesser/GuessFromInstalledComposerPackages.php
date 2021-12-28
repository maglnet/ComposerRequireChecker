<?php

declare(strict_types=1);

namespace ComposerRequireChecker\DependencyGuesser;

use Roave\BetterReflection\BetterReflection;
use Roave\BetterReflection\Identifier\Identifier;
use Roave\BetterReflection\Identifier\IdentifierType;
use Roave\BetterReflection\Reflection\ReflectionClass;
use Roave\BetterReflection\Reflector\DefaultReflector;
use Roave\BetterReflection\SourceLocator\Type\Composer\Factory\MakeLocatorForInstalledJson;
use Generator;
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

        $cleanPath = preg_quote(sprintf('%s/vendor', str_replace(DIRECTORY_SEPARATOR, '/', $installationPath)), '@');
        $this->pathRegex = sprintf('@^%s/(?:composer/\.\./)?([^/]+/[^/]+)/@', $cleanPath);
    }

    /**
     * @return Generator<string>
     */
    public function __invoke(string $symbolName): Generator
    {
        $reflection = $this->sourceLocator->locateIdentifier(
            new DefaultReflector($this->sourceLocator),
            new Identifier($symbolName, new IdentifierType(IdentifierType::IDENTIFIER_CLASS))
        );

        if (!($reflection instanceof ReflectionClass)) {
            return;
        }

        $path = $reflection->getFileName();

        if (preg_match($this->pathRegex, $path, $captures)) {
            yield $captures[1];
        }
    }
}
