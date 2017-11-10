<?php declare(strict_types=1);
namespace ComposerRequireChecker\GeneratorUtil;

use Generator;

final class ComposeGenerators
{
    public function __invoke(Generator ...$generators): Generator
    {
        foreach ($generators as $generator) {
            yield from $generator;
        }
    }
}
