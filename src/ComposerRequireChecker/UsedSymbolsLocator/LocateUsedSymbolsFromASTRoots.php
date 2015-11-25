<?php

namespace ComposerRequireChecker\UsedSymbolsLocator;

use ComposerRequireChecker\NodeVisitor\UsedSymbolCollector;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\NameResolver;
use PhpParser\Parser;
use Traversable;

final class LocateUsedSymbolsFromASTRoots
{
    /**
     * @param Traversable|array[] $ASTs a series of AST roots
     *
     * @return string[] all the found symbols
     */
    public function __invoke(Traversable $ASTs) : array
    {
        // note: dependency injection is not really feasible for these two, as they need to co-exist in parallel
        $usedSymbolsCollector = new UsedSymbolCollector();
        $usedSymbolsTraverser = new NodeTraverser();

        $usedSymbolsTraverser->addVisitor(new NameResolver());
        $usedSymbolsTraverser->addVisitor($usedSymbolsCollector);

        $astSymbols = [];

        foreach ($ASTs as $astRoot) {
            $usedSymbolsTraverser->traverse($astRoot);

            $astSymbols[] = $usedSymbolsCollector->getCollectedSymbols();
        }

        return array_values(array_unique(array_merge([], ...$astSymbols)));
    }
}
