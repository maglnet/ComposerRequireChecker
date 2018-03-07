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
    public function getDefinedSymbols(): array
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
        $this->recordDefinedConstDefinition($node);
    }

    private function recordClassDefinition(Node $node)
    {
        if ($node instanceof Node\Stmt\Class_ && !$node->isAnonymous()) {
            $this->recordDefinitionOf($node);
        }
    }

    private function recordInterfaceDefinition(Node $node)
    {
        if ($node instanceof Node\Stmt\Interface_) {
            $this->recordDefinitionOf($node);
        }
    }

    private function recordTraitDefinition(Node $node)
    {
        if ($node instanceof Node\Stmt\Trait_) {
            $this->recordDefinitionOf($node);
        }
    }

    private function recordFunctionDefinition(Node $node)
    {
        if ($node instanceof Node\Stmt\Function_) {
            $this->recordDefinitionOf($node);
        }
    }

    private function recordConstDefinition(Node $node)
    {
        if ($node instanceof Node\Stmt\Const_) {
            foreach ($node->consts as $const) {
                $this->recordDefinitionOf($const);
            }
        }
    }

    private function recordDefinedConstDefinition(Node $node)
    {
        if ($node instanceof Node\Expr\FuncCall && $node->name instanceof Node\Name && $node->name->toString() === 'define') {
            $this->recordDefinitionOfStringSymbol((string)$node->args[0]->value->value);
        }
    }

    /**
     * @param Node $node
     *
     * @return void
     */
    private function recordDefinitionOf(Node $node)
    {
        if (!isset($node->namespacedName)) {
            throw new \UnexpectedValueException(sprintf(
                'Given node of type "%s" (defined at line %s)does not have an assigned "namespacedName" property: '
                . 'did you pass it through a name resolver visitor?',
                get_class($node),
                $node->getLine()
            ));
        }

        $this->recordDefinitionOfStringSymbol((string)$node->namespacedName);
    }

    private function recordDefinitionOfStringSymbol(string $symbolName)
    {
        $this->definedSymbols[$symbolName] = $symbolName;
    }
}
