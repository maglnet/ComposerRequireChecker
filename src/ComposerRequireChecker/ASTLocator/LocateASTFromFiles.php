<?php

namespace ComposerRequireChecker\ASTLocator;

use PhpParser\ErrorHandler;
use PhpParser\Parser;
use Traversable;

final class LocateASTFromFiles
{
    /**
     * @var Parser
     */
    private $parser;

    /**
     * @var ErrorHandler
     */
    private $errorHandler;

    public function __construct(Parser $parser, ?ErrorHandler $errorHandler)
    {
        $this->parser = $parser;
        $this->errorHandler = $errorHandler;
    }

    /**
     * @param Traversable|string[] $files
     *
     * @return Traversable|FileAST[] a series of AST roots, one for each given file
     */
    public function __invoke(Traversable $files): Traversable
    {
        foreach ($files as $file) {
            yield new FileAST($file, $this->parser->parse(file_get_contents($file), $this->errorHandler));
        }
    }
}
