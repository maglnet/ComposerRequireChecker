<?php

declare(strict_types=1);

namespace ComposerRequireChecker\DependencyGuesser;

use Generator;

interface Guesser
{
    public function __invoke(string $symbolName): Generator;
}
