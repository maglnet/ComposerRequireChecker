<?php

namespace ComposerRequireChecker\GeneratorUtil;

use Traversable;

final class ComposeGenerators
{
    /**
     * @template TKey
     * @template TValue
     *
     * @param Traversable<TKey, TValue> ...$generators
     * @return Traversable<int, TValue>
     */
    public function __invoke(Traversable ...$generators): Traversable
    {
        foreach ($generators as $generator) {
            yield from $generator;
        }
    }
}
