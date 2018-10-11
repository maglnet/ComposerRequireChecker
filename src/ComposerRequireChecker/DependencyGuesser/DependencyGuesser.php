<?php

namespace ComposerRequireChecker\DependencyGuesser;

class DependencyGuesser
{

    /**
     * @var
     */
    private $guessers = [];

    public function __construct()
    {
        $this->guessers[] = new GuessFromLoadedExtensions();
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
