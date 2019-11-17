<?php

namespace ComposerRequireChecker\DependencyGuesser;

use ComposerRequireChecker\Cli\Options;

class DependencyGuesser
{

    /**
     * @var GuesserInterface[]
     */
    private $guessers = [];

    public function __construct(?Options $options = null)
    {
        $this->guessers[] = new GuessFromLoadedExtensions($options);
    }

    public function addGuesser(GuesserInterface $guesser): void
    {
        $this->guessers[] = $guesser;
    }

    public function __invoke($symbolName): \Generator
    {
        foreach ($this->guessers as $guesser) {
            yield from $guesser($symbolName);
        }
    }
}
