<?php

namespace ComposerRequireChecker\DependencyGuesser;

class DependencyGuesser
{

    /**
     * @var GuesserInterface[]
     */
    private $guessers = [];

    public function __construct(?array $options)
    {
        $this->guessers[] = new GuessFromLoadedExtensions($options['php_core_extensions'] ?? null);
    }

    public function __invoke($symbolName): \Generator
    {
        foreach ($this->guessers as $guesser) {
            yield from $guesser($symbolName);
        }
    }
}
