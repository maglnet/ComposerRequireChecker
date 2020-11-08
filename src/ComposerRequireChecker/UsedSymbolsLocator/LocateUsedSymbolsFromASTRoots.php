<?php

declare(strict_types=1);

namespace ComposerRequireChecker\UsedSymbolsLocator;

use ComposerRequireChecker\NodeVisitor\UsedSymbolCollector;
use PhpParser\Node;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\NameResolver;
use Traversable;

use function array_merge;
use function array_unique;
use function array_values;
use function natcasesort;

final class LocateUsedSymbolsFromASTRoots
{
    /**
     * @param Traversable<int, array<Node>> $ASTs a series of AST roots
     *
     * @return string[] all the found symbols
     */
    public function __invoke(Traversable $ASTs): array
    {
        // note: dependency injection is not really feasible for these two, as they need to co-exist in parallel
        $traverser = new NodeTraverser();

        $traverser->addVisitor(new NameResolver());
        $traverser->addVisitor($collector = new UsedSymbolCollector());

        $astSymbols = [];

        foreach ($ASTs as $astRoot) {
            $traverser->traverse($astRoot);

            $astSymbols[] = $collector->getCollectedSymbols();
        }

        $usedSymbols = array_unique(array_merge([], ...$astSymbols));

        natcasesort($usedSymbols);

        return array_values($usedSymbols);
    }
}
