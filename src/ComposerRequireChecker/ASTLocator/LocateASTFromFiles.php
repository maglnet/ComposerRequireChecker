<?php

declare(strict_types=1);

namespace ComposerRequireChecker\ASTLocator;

use ComposerRequireChecker\Exception\FileParseFailed;
use PhpParser\Error;
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
    public function __construct(private Parser $parser, private ErrorHandler|null $errorHandler)
    {
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

            try {
                $stmts = $this->parser->parse(file_get_contents($file), $this->errorHandler);
            } catch (Error $e) {
                // Convert the parse error into one which has the file information
                throw new FileParseFailed($file, $e);
            }

            if ($stmts === null) {
                throw new RuntimeException(sprintf('Parsing the file [%s] resulted in an error.', $file));
            }

            yield $stmts;
        }
    }
}
