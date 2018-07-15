<?php

namespace ComposerRequireChecker\DefinedSymbolsLocator;

use ArrayIterator;
use ComposerRequireChecker\NodeVisitor\DefinedSymbolCollector;
use ComposerRequireChecker\NodeVisitor\IncludeCollector;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\NameResolver;
use Traversable;

final class LocateDefinedSymbolsFromASTRoots
{
    /**
     * @param Traversable|array[] $ASTs a series of AST roots
     *
     * @return array [all the found symbols, includes to be processed]
     */
    public function __invoke(Traversable $ASTs, ?LocatedSymbolsAndIncludes $located = null): LocatedSymbolsAndIncludes
    {
        // note: dependency injection is not really feasible for these two, as they need to co-exist in parallel
        $traverser = new NodeTraverser();

        $traverser->addVisitor(new NameResolver());
        $traverser->addVisitor($collector = new DefinedSymbolCollector());
        $traverser->addVisitor($includes = new IncludeCollector());

        $astSymbols = [];
        $additionalFiles = [];

        foreach ($ASTs as $astRoot) {
            $traverser->traverse($astRoot->getAst());
            $astSymbols[] = $collector->getDefinedSymbols();
            $additionalFiles = array_merge($additionalFiles, $includes->getIncluded($astRoot->getFile()));
        }
        $located = $located ?? new LocatedSymbolsAndIncludes();

        return $located
            ->addSymbols($astSymbols)
            ->setIncludes($additionalFiles);
    }
}
