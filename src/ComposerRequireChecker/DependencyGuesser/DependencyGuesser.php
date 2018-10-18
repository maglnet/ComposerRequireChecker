<?php

namespace ComposerRequireChecker\DependencyGuesser;

class DependencyGuesser
{

    /**
     * @var GuesserInterface[]
     */
    private $guessers = [];

    public function __construct()
    {
        $this->guessers[] = new GuessFromLoadedExtensions();
    }

    public function __invoke($symbolName): \Generator
    {
        foreach ($this->guessers as $guesser) {
            yield from $guesser($symbolName);
        }
    }
}
