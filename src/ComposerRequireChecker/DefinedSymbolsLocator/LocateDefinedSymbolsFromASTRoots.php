<?php

declare(strict_types=1);

namespace ComposerRequireChecker\DefinedSymbolsLocator;

use ComposerRequireChecker\NodeVisitor\DefinedSymbolCollector;
use PhpParser\Node;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\NameResolver;
use Traversable;

use function array_merge;
use function array_unique;
use function array_values;

final class LocateDefinedSymbolsFromASTRoots
{
    /**
     * @param Traversable<int, array<Node>> $ASTs a series of AST roots
     *
     * @return list<string> all the found symbols
     */
    public function __invoke(Traversable $ASTs): array
    {
        // note: dependency injection is not really feasible for these two, as they need to co-exist in parallel
        $traverser = new NodeTraverser();

        $traverser->addVisitor(new NameResolver());
        $traverser->addVisitor($collector = new DefinedSymbolCollector());

        $astSymbols = [];

        foreach ($ASTs as $astRoot) {
            $traverser->traverse($astRoot);

            $astSymbols[] = $collector->getDefinedSymbols();
        }

        return array_values(array_unique(array_merge([], ...$astSymbols)));
    }
}
