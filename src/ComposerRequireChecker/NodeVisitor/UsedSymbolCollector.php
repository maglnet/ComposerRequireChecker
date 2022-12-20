<?php

declare(strict_types=1);

namespace ComposerRequireChecker\NodeVisitor;

use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;

use function array_filter;
use function array_keys;
use function array_map;

final class UsedSymbolCollector extends NodeVisitorAbstract
{
    /** @var array<string, string> */
    private array $collectedSymbols = [];

    public function __construct()
    {
    }

    /** @return list<string> */
    public function getCollectedSymbols(): array
    {
        return array_keys($this->collectedSymbols);
    }

    /**
     * {@inheritDoc}
     */
    public function beforeTraverse(array $nodes)
    {
        $this->collectedSymbols = [];

        return parent::beforeTraverse($nodes);
    }

    /**
     * {@inheritDoc}
     */
    public function enterNode(Node $node)
    {
        $this->recordExtendsUsage($node);
        $this->recordImplementsUsage($node);
        $this->recordClassExpressionUsage($node);
        $this->recordCatchUsage($node);
        $this->recordFunctionCallUsage($node);
        $this->recordFunctionParameterTypesUsage($node);
        $this->recordFunctionReturnTypeUsage($node);
        $this->recordConstantFetchUsage($node);
        $this->recordTraitUsage($node);
        $this->recordPropertyTypeUsage($node);

        return parent::enterNode($node);
    }

    private function recordExtendsUsage(Node $node): void
    {
        if ($node instanceof Node\Stmt\Class_) {
            array_map([$this, 'recordUsageOf'], array_filter([$node->extends]));
        }

        if (! ($node instanceof Node\Stmt\Interface_)) {
            return;
        }

        array_map([$this, 'recordUsageOf'], array_filter($node->extends));
    }

    private function recordImplementsUsage(Node $node): void
    {
        if (! ($node instanceof Node\Stmt\Class_)) {
            return;
        }

        array_map([$this, 'recordUsageOf'], $node->implements);
    }

    private function recordClassExpressionUsage(Node $node): void
    {
        if (
            ! (
            $node instanceof Node\Expr\StaticCall
            || $node instanceof Node\Expr\StaticPropertyFetch
            || $node instanceof Node\Expr\ClassConstFetch
            || $node instanceof Node\Expr\New_
            || $node instanceof Node\Expr\Instanceof_
            )
        ) {
            return;
        }

        if (! $node->class instanceof Node\Name) {
            return;
        }

        $this->recordUsageOf($node->class);
    }

    private function recordCatchUsage(Node $node): void
    {
        if (! ($node instanceof Node\Stmt\Catch_)) {
            return;
        }

        foreach ($node->types as $type) {
            $this->recordUsageOf($type);
        }
    }

    private function recordFunctionCallUsage(Node $node): void
    {
        if (
            ! ($node instanceof Node\Expr\FuncCall)
            || ! ($node->name instanceof Node\Name)
        ) {
            return;
        }

        $this->recordUsageOf($node->name);
    }

    private function recordFunctionParameterTypesUsage(Node $node): void
    {
        if (
            ! ($node instanceof Node\Stmt\Function_)
            && ! ($node instanceof Node\Stmt\ClassMethod)
        ) {
            return;
        }

        foreach ($node->getParams() as $param) {
            if ($param->type instanceof Node\Name) {
                $this->recordUsageOf($param->type);
            }

            if (! ($param->type instanceof Node\Identifier)) {
                continue;
            }

            $this->recordUsageOfByString($param->type->toString());
        }
    }

    private function recordFunctionReturnTypeUsage(Node $node): void
    {
        if (
            ! ($node instanceof Node\Stmt\Function_)
            && ! ($node instanceof Node\Stmt\ClassMethod)
        ) {
            return;
        }

        $returnType = $node->getReturnType();

        if ($returnType instanceof Node\Name) {
            $this->recordUsageOf($returnType);
        }

        if (! ($returnType instanceof Node\Identifier)) {
            return;
        }

        $this->recordUsageOfByString($returnType->toString());
    }

    private function recordConstantFetchUsage(Node $node): void
    {
        if (! ($node instanceof Node\Expr\ConstFetch)) {
            return;
        }

        $this->recordUsageOf($node->name);
    }

    private function recordTraitUsage(Node $node): void
    {
        if (! $node instanceof Node\Stmt\TraitUse) {
            return;
        }

        array_map([$this, 'recordUsageOf'], $node->traits);

        foreach ($node->adaptations as $adaptation) {
            if ($adaptation->trait !== null) {
                $this->recordUsageOf($adaptation->trait);
            }

            if (! ($adaptation instanceof Node\Stmt\TraitUseAdaptation\Precedence)) {
                continue;
            }

            array_map([$this, 'recordUsageOf'], $adaptation->insteadof);
        }
    }

    private function recordPropertyTypeUsage(Node $node): void
    {
        if (! $node instanceof Node\Stmt\Property) {
            return;
        }

        if (! $node->type instanceof Node\Name) {
            return;
        }

        $this->recordUsageOf($node->type);
    }

    private function recordUsageOf(Node\Name $symbol): void
    {
        $this->recordUsageOfByString($symbol->toString());
    }

    private function recordUsageOfByString(string $symbol): void
    {
        $this->collectedSymbols[$symbol] = $symbol;
    }
}
