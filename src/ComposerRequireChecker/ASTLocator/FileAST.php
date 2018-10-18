<?php

namespace ComposerRequireChecker\ASTLocator;

use PhpParser\Node;

class FileAST
{
    /**
     * @var string
     */
    private $file;

    /**
     * @var array
     */
    private $ast;

    /**
     * @param string $file
     * @param array|null $ast
     *
     */
    public function __construct(string $file, ?array $ast)
    {
        $this->file = $file;
        $this->ast = $ast ?? [];
    }

    /**
     * @return string
     */
    public function getFile(): string
    {
        return $this->file;
    }

    /**
     * @return Node[]
     */
    public function getAst(): array
    {
        return $this->ast;
    }
}
