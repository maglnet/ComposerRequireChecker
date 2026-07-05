<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;

return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/src',
        __DIR__ . '/test',
    ])
    ->withSkip([
        __DIR__ . '/test/fixtures'
    ])
    ->withPhpSets();
