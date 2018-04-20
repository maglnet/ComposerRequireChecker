<?php

namespace ComposerRequireChecker\NodeVisitor;

use FilesystemIterator;
use InvalidArgumentException;
use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\BinaryOp\Concat;
use PhpParser\Node\Expr\ConstFetch;
use PhpParser\Node\Expr\Include_;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Scalar\MagicConst\Dir;
use PhpParser\Node\Scalar\MagicConst\File;
use PhpParser\Node\Scalar\String_;
use PhpParser\NodeVisitorAbstract;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

final class IncludeCollector extends NodeVisitorAbstract
{
    /**
     * @var Expr[]
     */
    private $included = [];

    /**
     * {@inheritDoc}
     */
    public function beforeTraverse(array $nodes)
    {
        $this->included = [];
        return parent::beforeTraverse($nodes);
    }

    /**
     * @param string $file
     * @return string[]
     */
    public function getIncluded(string $file): array
    {
        $included = [];
        foreach ($this->included as $exp) {
            try {
                $this->computePath($included, $this->processIncludePath($exp, $file), $file);
            } catch(InvalidArgumentException $x) {
                var_dump($x->getMessage());
            }
        }
        return $included;
    }

    /**
     * @param array $included
     * @param string $path
     * @param string $self
     * @return void
     */
    private function computePath(array &$included, string $path, string $self)
    {
        if (!preg_match('#^([A-Z]:)?/#i', str_replace('\\', '/', $path))) {
            $path = dirname($self).'/'.$path;
        }
        if (false === strpos($path, '{var}')) {
            $included[] = $path;
            return;
        }
        $parts = explode('{var}', $path);
        $regex = [];
        foreach($parts as $part) {
            $regex[] = preg_quote(str_replace('\\', '/', $part), '/');
        }
        $regex = '/^'.implode('.+', $regex).'$/';
        $self = str_replace('\\', '/', $self);
        foreach (new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(
                $parts[0],
                FilesystemIterator::CURRENT_AS_PATHNAME | FilesystemIterator::SKIP_DOTS
            )
        ) as $file) {
            $rfile = str_replace('\\', '/', $file);
            if ($rfile !== $self && preg_match('/\\.php$/i', $rfile) && preg_match($regex, $rfile)) {
                $included[] = $file;
            }
        }
    }

    /**
     * @param string|Exp $exp
     * @param string $file
     * @return string
     * @throws InvalidArgumentException
     */
    private function processIncludePath($exp, string $file): string
    {
        if (is_string($exp)) {
            return $exp;
        }
        if ($exp instanceof Concat) {
            return $this->processIncludePath($exp->left, $file).$this->processIncludePath($exp->right, $file);
        }
        if ($exp instanceof Dir) {
            return dirname($file);
        }
        if ($exp instanceof File) {
            return $file;
        }
        if ($exp instanceof ConstFetch && $exp->name === 'DIRECTORY_SEPARATOR') {
            return DIRECTORY_SEPARATOR;
        }
        if ($exp instanceof String_) {
            return $exp->value;
        }
        if ($exp instanceof Variable || $exp instanceof ConstFetch) {
            return '{var}';
        }
        throw new InvalidArgumentException('can\'t yet handle '.$exp->getType());
    }

    /**
     * {@inheritDoc}
     */
    public function enterNode(Node $node)
    {
        if ($node instanceof Include_) {
            $this->included[] = $node->expr;
        }
    }
}
