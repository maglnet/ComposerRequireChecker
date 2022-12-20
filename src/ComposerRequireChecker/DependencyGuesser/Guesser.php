<?php

declare(strict_types=1);

namespace ComposerRequireChecker\DependencyGuesser;

use Generator;

interface Guesser
{
    /** @return Generator<string> */
    public function __invoke(string $symbolName): Generator;
}
