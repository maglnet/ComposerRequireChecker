<?php declare(strict_types=1);


namespace ComposerRequireChecker\DependencyGuesser;

use Roave\BetterReflection\BetterReflection;
use Roave\BetterReflection\Identifier\Identifier;
use Roave\BetterReflection\Identifier\IdentifierType;
use Roave\BetterReflection\Reflection\ReflectionClass;
use Roave\BetterReflection\Reflector\ClassReflector;
use Roave\BetterReflection\SourceLocator\Type\Composer\Factory\MakeLocatorForInstalledJson;
use Roave\BetterReflection\SourceLocator\Type\MemoizingSourceLocator;
use Roave\BetterReflection\SourceLocator\Type\SourceLocator;

final class GuessFromComposerInstalledJson implements GuesserInterface
{
    /**
     * @var SourceLocator
     */
    private $sourceLocator;

    /**
     * @var ClassReflector
     */
    private $reflector;

    /**
     * @var string
     */
    private $pathRegex;

    public function __construct(string $projectPath)
    {
        $this->sourceLocator = new MemoizingSourceLocator(
            (new MakeLocatorForInstalledJson())(
                $projectPath,
                (new BetterReflection())->astLocator()
            )
        );

        $this->reflector = new ClassReflector($this->sourceLocator);

        // @todo: support https://getcomposer.org/doc/06-config.md#vendor-dir; useless as BetterReflection does not, at this moment.
        $cleanPath = preg_quote(str_replace(DIRECTORY_SEPARATOR, '/', $projectPath) . '/' . 'vendor', '@');

        $this->pathRegex = '@^' . $cleanPath . '/(?:composer/\.\./)?([^/]+/[^/]+)/@';
    }

    public function __invoke(string $symbolName): \Generator
    {
        $reflection = $this->sourceLocator->locateIdentifier($this->reflector, new Identifier($symbolName, new IdentifierType(IdentifierType::IDENTIFIER_CLASS)));

        if (!($reflection instanceof ReflectionClass)) {
            return;
        }

        $path = $reflection->getFileName();

        if (preg_match($this->pathRegex, $path, $captures)) {
            yield $captures[1];
        }
    }
}
