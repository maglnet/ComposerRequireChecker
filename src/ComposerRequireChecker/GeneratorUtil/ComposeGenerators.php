<?php

declare(strict_types=1);

namespace ComposerRequireChecker\GeneratorUtil;

use Traversable;

final class ComposeGenerators
{
    /**
     * @param  Traversable<TKey, TValue> ...$generators
     *
     * @return Traversable<int, TValue>
     *
     * @template TKey
     * @template TValue
     */
    public function __invoke(Traversable ...$generators): Traversable
    {
        foreach ($generators as $generator) {
            yield from $generator;
        }
    }
}
