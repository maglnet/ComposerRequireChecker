<?php

namespace ComposerRequireChecker\NodeVisitor;

use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;

final class UsedSymbolCollector extends NodeVisitorAbstract
{
    /**
     * @var mixed[]
     */
    private $collectedSymbols = [];

    public function __construct()
    {
    }

    /**
     * @return string[]
     */
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

        return parent::enterNode($node);
    }

    private function recordExtendsUsage(Node $node)
    {
        if ($node instanceof Node\Stmt\Class_) {
            array_map([$this, 'recordUsageOf'], array_filter([$node->extends]));
        }

        if ($node instanceof Node\Stmt\Interface_) {
            array_map([$this, 'recordUsageOf'], array_filter($node->extends));
        }
    }

    private function recordImplementsUsage(Node $node)
    {
        if ($node instanceof Node\Stmt\Class_) {
            array_map([$this, 'recordUsageOf'], $node->implements);
        }
    }

    private function recordClassExpressionUsage(Node $node)
    {
        if (($node instanceof Node\Expr\StaticCall
                || $node instanceof Node\Expr\StaticPropertyFetch
                || $node instanceof Node\Expr\ClassConstFetch
                || $node instanceof Node\Expr\New_
                || $node instanceof Node\Expr\Instanceof_
            )
            && ($nodeClass = $node->class)
            && $nodeClass instanceof Node\Name
        ) {
            $this->recordUsageOf($nodeClass);
        }
    }

    private function recordCatchUsage(Node $node)
    {
        if ($node instanceof Node\Stmt\Catch_) {
            foreach ($node->types as $type) {
                $this->recordUsageOf($type);
            }
        }
    }

    private function recordFunctionCallUsage(Node $node)
    {
        if ($node instanceof Node\Expr\FuncCall
            && ($nodeName = $node->name)
            && $nodeName instanceof Node\Name
        ) {
            $this->recordUsageOf($nodeName);
        }
    }

    private function recordFunctionParameterTypesUsage(Node $node)
    {
        if ($node instanceof Node\Stmt\Function_
            || $node instanceof Node\Stmt\ClassMethod
        ) {
            foreach ($node->getParams() as $param) {
                if ($param->type instanceof Node\Name) {
                    $this->recordUsageOf($param->type);
                }
                if (is_string($param->type) || $param->type instanceof Node\Identifier) {
                    $this->recordUsageOfByString($param->type);
                }
            }
        }
    }

    private function recordFunctionReturnTypeUsage(Node $node)
    {
        if ($node instanceof Node\Stmt\Function_
            || $node instanceof Node\Stmt\ClassMethod
        ) {
            if ($node->getReturnType() instanceof Node\Name) {
                $this->recordUsageOf($node->getReturnType());
            }
            if (is_string($node->getReturnType()) || $node->getReturnType() instanceof Node\Identifier) {
                $this->recordUsageOfByString($node->getReturnType());
            }
        }
    }

    private function recordConstantFetchUsage(Node $node)
    {
        if ($node instanceof Node\Expr\ConstFetch) {
            $this->recordUsageOf($node->name);
        }
    }

    private function recordTraitUsage(Node $node)
    {
        if (!$node instanceof Node\Stmt\TraitUse) {
            return;
        }

        array_map([$this, 'recordUsageOf'], $node->traits);

        foreach ($node->adaptations as $adaptation) {
            if (null !== $adaptation->trait) {
                $this->recordUsageOf($adaptation->trait);
            }

            if ($adaptation instanceof Node\Stmt\TraitUseAdaptation\Precedence) {
                array_map([$this, 'recordUsageOf'], $adaptation->insteadof);
            }
        }
    }

    /**
     * @param Node\Name $symbol
     *
     * @return void
     */
    private function recordUsageOf(Node\Name $symbol)
    {
        $this->collectedSymbols[(string)$symbol] = $symbol;
    }

    /**
     * @param string $symbol
     *
     * @return void
     */
    private function recordUsageOfByString(string $symbol)
    {
        $this->collectedSymbols[$symbol] = $symbol;
    }
}
