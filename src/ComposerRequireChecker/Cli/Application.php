<?php

declare(strict_types=1);

namespace ComposerRequireChecker\Cli;

use PackageVersions\Versions;
use Symfony\Component\Console\Application as AbstractApplication;

class Application extends AbstractApplication
{
    public function __construct()
    {
        parent::__construct(
            'ComposerRequireChecker',
            Versions::getVersion('maglnet/composer-require-checker')
        );

        $check = new CheckCommand();
        $this->add($check);
        $this->setDefaultCommand(CheckCommand::NAME);
    }
}
