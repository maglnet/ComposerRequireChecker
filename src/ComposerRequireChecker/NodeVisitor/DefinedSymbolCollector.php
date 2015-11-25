<?php

namespace ComposerRequireChecker\NodeVisitor;

use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;

final class DefinedSymbolCollector extends NodeVisitorAbstract
{
    /**
     * @var mixed[]
     */
    private $definedSymbols = [];

    public function __construct()
    {
    }

    /**
     * {@inheritDoc}
     */
    public function beforeTraverse(array $nodes)
    {
        $this->definedSymbols = [];

        return parent::beforeTraverse($nodes);
    }

    /**
     * @return string[]
     */
    public function getDefinedSymbols() : array
    {
        return array_keys($this->definedSymbols);
    }

    /**
     * {@inheritDoc}
     */
    public function enterNode(Node $node)
    {
        $this->recordClassDefinition($node);
        $this->recordInterfaceDefinition($node);
        $this->recordTraitDefinition($node);
        $this->recordFunctionDefinition($node);
        $this->recordConstDefinition($node);
    }

    private function recordClassDefinition(Node $node)
    {
        if ($node instanceof Node\Stmt\Class_) {
            $this->recordDefinitionOf($node->name);
        }
    }

    private function recordInterfaceDefinition(Node $node)
    {
        if ($node instanceof Node\Stmt\Interface_) {
            $this->recordDefinitionOf($node->name);
        }
    }

    private function recordTraitDefinition(Node $node)
    {
        if ($node instanceof Node\Stmt\Trait_) {
            $this->recordDefinitionOf($node->name);
        }
    }

    private function recordFunctionDefinition(Node $node)
    {
        if ($node instanceof Node\Stmt\Function_) {
            $this->recordDefinitionOf($node->name);
        }
    }

    private function recordConstDefinition(Node $node)
    {
        if ($node instanceof Node\Stmt\Const_) {
            foreach ($node->consts as $const) {
                $this->recordDefinitionOf($const->name);
            }
        }
    }

    /**
     * @param string $symbolName
     *
     * @return void
     */
    private function recordDefinitionOf(string $symbolName)
    {
        $this->definedSymbols[$symbolName] = $symbolName;
    }
}
