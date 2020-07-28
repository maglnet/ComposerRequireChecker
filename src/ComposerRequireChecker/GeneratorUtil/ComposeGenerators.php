<?php

namespace ComposerRequireChecker\GeneratorUtil;

use Traversable;

final class ComposeGenerators
{
    public function __invoke(Traversable ...$generators): Traversable
    {
        foreach ($generators as $generator) {
            yield from $generator;
        }
    }
}
