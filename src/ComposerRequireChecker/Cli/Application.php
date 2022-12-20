<?php

declare(strict_types=1);

namespace ComposerRequireChecker\Cli;

use Composer\InstalledVersions;
use Symfony\Component\Console\Application as AbstractApplication;

use function sprintf;

class Application extends AbstractApplication
{
    public function __construct()
    {
        parent::__construct(
            'ComposerRequireChecker',
            sprintf(
                '%s@%s',
                (string) InstalledVersions::getPrettyVersion('maglnet/composer-require-checker'),
                (string) InstalledVersions::getReference('maglnet/composer-require-checker'),
            ),
        );

        $check = new CheckCommand();
        $this->add($check);
        $this->setDefaultCommand(CheckCommand::NAME);
    }
}
