<?php

declare(strict_types=1);

namespace Example\Library;

use Foo\Bar\Baz;

final class OtherThing
{
    public function baz(Baz $baz)
    {
        $collection = new \Doctrine\Common\Collections\ArrayCollection([]);

        libxml_clear_errors();

        filter_var(
            $baz->value(),
            FILTER_VALIDATE_URL
        );
    }
}
