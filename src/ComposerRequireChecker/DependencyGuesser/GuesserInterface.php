<?php declare(strict_types=1);
namespace ComposerRequireChecker\DependencyGuesser;

interface GuesserInterface
{

    /**
     * @param string $symbolName
     * @return \Generator
     */
    public function __invoke(string $symbolName): \Generator;
}
