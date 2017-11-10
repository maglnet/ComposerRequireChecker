<?php declare(strict_types=1);
namespace ComposerRequireChecker\ASTLocator;

use PhpParser\Parser;
use Traversable;

final class LocateASTFromFiles
{
    /**
     * @var Parser
     */
    private $parser;

    public function __construct(Parser $parser)
    {
        $this->parser = $parser;
    }

    /**
     * @param Traversable $files
     *
     * @return Traversable|array[] a series of AST roots, one for each given file
     */
    public function __invoke(Traversable $files): Traversable
    {
        foreach ($files as $file) {
            yield $this->parser->parse(file_get_contents($file));
        }
    }
}
