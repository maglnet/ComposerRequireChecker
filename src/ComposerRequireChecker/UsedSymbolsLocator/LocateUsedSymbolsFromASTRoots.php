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
        $traverser = new NodeTraverser();

        $traverser->addVisitor(new NameResolver());
        $traverser->addVisitor($collector = new UsedSymbolCollector());

        $astSymbols = [];

        foreach ($ASTs as $astRoot) {
            $traverser->traverse($astRoot);

            $astSymbols[] = $collector->getCollectedSymbols();
        }

        return array_values(array_unique(array_merge([], ...$astSymbols)));
    }
}
