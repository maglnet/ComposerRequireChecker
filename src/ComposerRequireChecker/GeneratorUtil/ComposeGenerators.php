<?php

namespace ComposerRequireChecker\GeneratorUtil;

use Generator;

final class ComposeGenerators
{
    public function __invoke(Generator ...$generators) : Generator
    {
        foreach ($generators as $generator) {
            yield from $generator;
        }
    }
}
