<?php

if (version_compare(PHP_VERSION, '7.1.0') < 0) {
    fwrite(STDERR, "PHP 7.1 is required\n");
    exit(1);
}

$autoloadFileLocations = [
    __DIR__ . '/../vendor/autoload.php',
    __DIR__ . '/../../../../vendor/autoload.php',
];

$foundAutoloadFile = false;
foreach ($autoloadFileLocations as $autoloadFileLocation) {
    if (!file_exists($autoloadFileLocation)) {
        continue;
    }

    $foundAutoloadFile = true;
    require $autoloadFileLocation;
    break;
}

if (false === $foundAutoloadFile) {
    fprintf(STDERR, "Could not find Composer autoloader! Checked paths: %s\n", implode(', ', $autoloadFileLocations));
    exit(2);
}

use ComposerRequireChecker\Cli\Application;

$application = new Application();
$application->run();
