<?php

declare(strict_types=1);

namespace ComposerRequireChecker\DependencyGuesser;

use Generator;

class DependencyGuesser
{
    /** @var array<Guesser> */
    private array $guessers = [];

    /**
     * @param array<Guesser> $guessers
     */
    public function __construct(array $guessers)
    {
        $this->guessers = $guessers;
    }

    /**
     * @return Generator<string>
     */
    public function __invoke(string $symbolName): Generator
    {
        foreach ($this->guessers as $guesser) {
            yield from $guesser($symbolName);
        }
    }
}
