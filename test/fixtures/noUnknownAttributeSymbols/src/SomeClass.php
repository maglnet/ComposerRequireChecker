<?php

declare(strict_types=1);

namespace Example\Library;

use AllowDynamicProperties;
use ReturnTypeWillChange;

#[WellKnownAttribute]
final class SomeClass
{
    #[ReturnTypeWillChange]
    public function test(): void
    {
    }

    #[AllowDynamicProperties]
    public function make(): void
    {
    }
}
