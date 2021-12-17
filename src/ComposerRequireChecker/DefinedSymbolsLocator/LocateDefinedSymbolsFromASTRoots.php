<?php

declare(strict_types=1);

namespace ComposerRequireChecker\DefinedSymbolsLocator;

use ComposerRequireChecker\ASTLocator\ASTLoader;
use ComposerRequireChecker\NodeVisitor\DefinedSymbolCollector;
use ComposerRequireChecker\SymbolCache;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\NameResolver;
use Traversable;

use function array_merge;
use function array_unique;
use function array_values;
use function is_file;

final class LocateDefinedSymbolsFromASTRoots
{
    private ASTLoader $astLoader;
    private SymbolCache $cache;

    public function __construct(ASTLoader $astLoader, SymbolCache $cache)
    {
        $this->astLoader = $astLoader;
        $this->cache     = $cache;
    }

    /**
     * @param Traversable<string> $files a list of files
     *
     * @return list<string> all the found symbols
     */
    public function __invoke(Traversable $files): array
    {
        // note: dependency injection is not really feasible for these two, as they need to co-exist in parallel
        $traverser = new NodeTraverser();

        $traverser->addVisitor(new NameResolver());
        $traverser->addVisitor($collector = new DefinedSymbolCollector());

        $astSymbols = [];

        foreach ($files as $file) {
            if (! is_file($file)) {
                continue;
            }

            $astSymbols[] = $this->cache->__invoke(
                $file,
                function () use ($collector, $traverser, $file) {
                    $astRoot = $this->astLoader->__invoke($file);
                    $traverser->traverse($astRoot);

                    return $collector->getDefinedSymbols();
                }
            );
        }

        return array_values(array_unique(array_merge([], ...$astSymbols)));
    }
}
