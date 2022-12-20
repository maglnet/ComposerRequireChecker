<?php

declare(strict_types=1);

namespace ComposerRequireChecker\Cli;

use Symfony\Component\Console\Application as AbstractApplication;
use Symfony\Component\Console\Output\OutputInterface;

final class ApplicationHeaderWriter
{
    public function __construct(private AbstractApplication|null $application = null)
    {
    }

    public function __invoke(OutputInterface $output): void
    {
        if ($output->isQuiet()) {
            return;
        }

        if ($this->application === null) {
            $output->writeln('Unknown version');

            return;
        }

        $output->writeln($this->application->getLongVersion());
    }
}
