<?php

declare(strict_types=1);

namespace ComposerRequireChecker\ASTLocator;

use PhpParser\ErrorHandler;
use PhpParser\Node\Stmt;
use PhpParser\Parser;
use RuntimeException;
use Traversable;

use function file_get_contents;
use function is_file;
use function sprintf;

final class LocateASTFromFiles
{
    private Parser $parser;
    private ?ErrorHandler $errorHandler;

    public function __construct(Parser $parser, ?ErrorHandler $errorHandler)
    {
        $this->parser       = $parser;
        $this->errorHandler = $errorHandler;
    }

    /**
     * @param Traversable<string> $files
     *
     * @return Traversable<int, array<Stmt>> a series of AST roots, one for each given file
     */
    public function __invoke(Traversable $files): Traversable
    {
        foreach ($files as $file) {
            if (! is_file($file)) {
                continue;
            }

            $stmts = $this->parser->parse(file_get_contents($file), $this->errorHandler);

            if ($stmts === null) {
                throw new RuntimeException(sprintf('Parsing the file [%s] resulted in an error.', $file));
            }

            yield $stmts;
        }
    }
}
