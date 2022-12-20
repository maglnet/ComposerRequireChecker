<?php

declare(strict_types=1);

namespace ComposerRequireChecker\Cli\ResultsWriter;

interface ResultsWriter
{
    /** @param array<array-key, list<string>> $unknownSymbols the unknown symbols found */
    public function write(array $unknownSymbols): void;
}
