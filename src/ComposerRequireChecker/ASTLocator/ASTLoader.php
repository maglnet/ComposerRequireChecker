<?php

declare(strict_types=1);

namespace ComposerRequireChecker\ASTLocator;

use ComposerRequireChecker\Exception\FileParseFailed;
use PhpParser\Error;
use PhpParser\ErrorHandler;
use PhpParser\Node\Stmt;
use PhpParser\Parser;
use RuntimeException;

use function file_get_contents;
use function sprintf;

final class ASTLoader
{
    private Parser $parser;
    private ?ErrorHandler $errorHandler;

    public function __construct(Parser $parser, ?ErrorHandler $errorHandler)
    {
        $this->parser       = $parser;
        $this->errorHandler = $errorHandler;
    }

    /**
     * @return array<Stmt>
     */
    public function __invoke(string $file): array
    {
        try {
            $stmts = $this->parser->parse(file_get_contents($file), $this->errorHandler);
        } catch (Error $e) {
            // Convert the parse error into one which has the file information
            throw new FileParseFailed($file, $e);
        }

        if ($stmts === null) {
            throw new RuntimeException(sprintf('Parsing the file [%s] resulted in an error.', $file));
        }

        return $stmts;
    }
}
