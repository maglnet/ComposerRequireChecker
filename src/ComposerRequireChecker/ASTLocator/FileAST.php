<?php

namespace ComposerRequireChecker\ASTLocator;

use Traversable;

class FileAST
{
    /**
     * @var string
     */
    private $file;

    /**
     * @var Traversable
     */
    private $ast;

    /**
     * @param string $file
     * @param Traversable|array|null $ast
     *
     */
    public function __construct(string $file, $ast)
    {
        $this->file = $file;
        $this->ast = $ast;
    }

    /**
     * @return string
     */
    public function getFile()
    {
        return $this->file;
    }

    /**
     * @return Traversable|array
     */
    public function getAst()
    {
        return $this->ast;
    }
}
