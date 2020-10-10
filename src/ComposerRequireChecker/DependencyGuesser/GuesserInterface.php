<?php

declare(strict_types=1);

namespace ComposerRequireChecker\DependencyGuesser;

use Generator;

interface GuesserInterface
{
    public function __invoke(string $symbolName): Generator;
}
