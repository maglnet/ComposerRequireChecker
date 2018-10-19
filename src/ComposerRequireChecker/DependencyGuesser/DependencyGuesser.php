<?php

namespace ComposerRequireChecker\DependencyGuesser;

class DependencyGuesser
{

    /**
     * @var GuesserInterface[]
     */
    private $guessers = [];

    public function __construct(GuesserInterface ...$guessers)
    {
        $this->guessers[] = new GuessFromLoadedExtensions();
        foreach ($guessers as $guesser) {
            $this->guessers[] = $guesser;
        }
    }

    public function __invoke($symbolName): \Generator
    {
        foreach ($this->guessers as $guesser) {
            yield from $guesser($symbolName);
        }
    }
}
