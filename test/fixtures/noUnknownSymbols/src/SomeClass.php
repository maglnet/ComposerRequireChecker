<?php

declare(strict_types=1);

namespace Example\Library;

use function strlen;

final class SomeClass
{
    public function someMethod(string $foo): int
    {
        return strlen($foo);
    }
}
