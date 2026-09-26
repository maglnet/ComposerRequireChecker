<?php

declare(strict_types=1);

namespace ComposerRequireChecker\FileLocator;

use Generator;
use PhpParser\Error;
use PhpParser\ErrorHandler\Collecting as CollectingErrorHandler;
use PhpParser\Node;
use PhpParser\NodeFinder;
use PhpParser\ParserFactory;
use Psl\File;
use Psl\Filesystem;
use Psl\Type;

use function is_file;
use function ltrim;
use function str_replace;

final class LocateFilesViaRequireStatements
{
    /** @return Generator<string> */
    public function __invoke(string $filePath): Generator
    {
        if (! is_file($filePath)) {
            return;
        }

        $path = Type\non_empty_string()->coerce($filePath);

        try {
            $content = File\read($path);
        } catch (File\Exception\NotFoundException) {
            return;
        }

        $errorHandler = new CollectingErrorHandler();
        $parser       = (new ParserFactory())->createForNewestSupportedVersion();

        try {
            $stmts = $parser->parse($content, $errorHandler);
        } catch (Error) {
            return;
        }

        if ($stmts === null) {
            return;
        }

        $fileDir = Filesystem\get_directory($path);

        /** @var Node\Expr\Include_[] $includes */
        $includes = (new NodeFinder())->findInstanceOf($stmts, Node\Expr\Include_::class);

        foreach ($includes as $include) {
            $resolvedPath = $this->resolvePath($include->expr, $fileDir);

            if ($resolvedPath !== null && is_file($resolvedPath)) {
                yield $resolvedPath;
            }
        }
    }

    private function resolvePath(Node\Expr $expr, string $fileDir): ?string
    {
        if ($expr instanceof Node\Scalar\String_) {
            return $this->normalizePath($fileDir . '/' . ltrim($expr->value, '/'));
        }

        if (
            $expr instanceof Node\Expr\BinaryOp\Concat
            && $expr->left instanceof Node\Scalar\MagicConst\Dir
            && $expr->right instanceof Node\Scalar\String_
        ) {
            return $this->normalizePath($fileDir . $expr->right->value);
        }

        return null;
    }

    private function normalizePath(string $path): string
    {
        // @infection-ignore-all UnwrapStrReplace False positive on Linux; this guards against Windows paths.
        return str_replace('\\', '/', $path);
    }
}
