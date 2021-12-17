<?php

declare(strict_types=1);

namespace ComposerRequireChecker\UsedSymbolsLocator;

use ComposerRequireChecker\ASTLocator\ASTLoader;
use ComposerRequireChecker\NodeVisitor\UsedSymbolCollector;
use ComposerRequireChecker\SymbolCache;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\NameResolver;
use Traversable;

use function array_merge;
use function array_unique;
use function array_values;
use function is_file;
use function natcasesort;

final class LocateUsedSymbolsFromASTRoots
{
    private ASTLoader $astLoader;
    private SymbolCache $cache;

    public function __construct(ASTLoader $astLoader, SymbolCache $cache)
    {
        $this->astLoader = $astLoader;
        $this->cache     = $cache;
    }

    /**
     * @param Traversable<string> $files
     *
     * @return string[] all the found symbols
     */
    public function __invoke(Traversable $files): array
    {
        // note: dependency injection is not really feasible for these two, as they need to co-exist in parallel
        $traverser = new NodeTraverser();

        $traverser->addVisitor(new NameResolver());
        $traverser->addVisitor($collector = new UsedSymbolCollector());

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

                    return $collector->getCollectedSymbols();
                }
            );
        }

        $usedSymbols = array_unique(array_merge([], ...$astSymbols));

        natcasesort($usedSymbols);

        return array_values($usedSymbols);
    }
}
