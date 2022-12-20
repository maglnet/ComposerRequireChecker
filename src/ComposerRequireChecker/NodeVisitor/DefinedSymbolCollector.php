<?php

declare(strict_types=1);

namespace ComposerRequireChecker\NodeVisitor;

use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;
use UnexpectedValueException;

use function array_keys;
use function property_exists;
use function sprintf;

final class DefinedSymbolCollector extends NodeVisitorAbstract
{
    /** @var array<string, string> */
    private array $definedSymbols = [];

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

    /** @return list<string> */
    public function getDefinedSymbols(): array
    {
        return array_keys($this->definedSymbols);
    }

    public function enterNode(Node $node): Node
    {
        $this->recordClassDefinition($node);
        $this->recordEnumDefinition($node);
        $this->recordInterfaceDefinition($node);
        $this->recordTraitDefinition($node);
        $this->recordFunctionDefinition($node);
        $this->recordConstDefinition($node);
        $this->recordDefinedConstDefinition($node);

        return $node;
    }

    private function recordClassDefinition(Node $node): void
    {
        if (! ($node instanceof Node\Stmt\Class_) || $node->isAnonymous()) {
            return;
        }

        $this->recordDefinitionOf($node);
    }

    private function recordEnumDefinition(Node $node): void
    {
        if (! ($node instanceof Node\Stmt\Enum_)) {
            return;
        }

        $this->recordDefinitionOf($node);
    }

    private function recordInterfaceDefinition(Node $node): void
    {
        if (! ($node instanceof Node\Stmt\Interface_)) {
            return;
        }

        $this->recordDefinitionOf($node);
    }

    private function recordTraitDefinition(Node $node): void
    {
        if (! ($node instanceof Node\Stmt\Trait_)) {
            return;
        }

        $this->recordDefinitionOf($node);
    }

    private function recordFunctionDefinition(Node $node): void
    {
        if (! ($node instanceof Node\Stmt\Function_)) {
            return;
        }

        $this->recordDefinitionOf($node);
    }

    private function recordConstDefinition(Node $node): void
    {
        if (! ($node instanceof Node\Stmt\Const_)) {
            return;
        }

        foreach ($node->consts as $const) {
            $this->recordDefinitionOf($const);
        }
    }

    private function recordDefinedConstDefinition(Node $node): void
    {
        if (
            ! ($node instanceof Node\Expr\FuncCall)
            || ! ($node->name instanceof Node\Name)
            || $node->name->toString() !== 'define'
        ) {
            return;
        }

        if ($node->name->hasAttribute('namespacedName')) {
            /** @var mixed $namespacedName */
            $namespacedName = $node->name->getAttribute('namespacedName');
            if ($namespacedName instanceof Node\Name\FullyQualified && $namespacedName->toString() !== 'define') {
                return;
            }
        }

        if (! ($node->args[0] instanceof Node\Arg)) {
            return;
        }

        if (! ($node->args[0]->value instanceof Node\Scalar\String_)) {
            return;
        }

        $this->recordDefinitionOfStringSymbol($node->args[0]->value->value);
    }

    /** @psalm-param Node\Stmt\Function_|Node\Stmt\ClassLike|Node\Const_ $node */
    private function recordDefinitionOf(Node $node): void
    {
        $namespacedName = null;
        if (property_exists($node, 'namespacedName')) {
            $namespacedName = $node->namespacedName;
        }

        if ($namespacedName === null) {
            throw new UnexpectedValueException(
                sprintf(
                    'Given node of type "%s" (defined at line %s)does not have an assigned "namespacedName" property: '
                    . 'did you pass it through a name resolver visitor?',
                    $node::class,
                    $node->getLine(),
                ),
            );
        }

        $this->recordDefinitionOfStringSymbol((string) $namespacedName);
    }

    private function recordDefinitionOfStringSymbol(string $symbolName): void
    {
        $this->definedSymbols[$symbolName] = $symbolName;
    }
}
