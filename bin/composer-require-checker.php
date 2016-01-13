<?php

if (PHP_MAJOR_VERSION < 7) {
    fwrite(STDERR, "PHP7 is required\n");
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
