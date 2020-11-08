<?php

declare(strict_types=1);

namespace ComposerRequireChecker\DependencyGuesser;

use ComposerRequireChecker\Cli\Options;
use Generator;

class DependencyGuesser
{
    /** @var Guesser[] */
    private array $guessers = [];

    public function __construct(?Options $options = null)
    {
        $this->guessers[] = new GuessFromLoadedExtensions($options);
    }

    public function __invoke(string $symbolName): Generator
    {
        foreach ($this->guessers as $guesser) {
            yield from $guesser($symbolName);
        }
    }
}
