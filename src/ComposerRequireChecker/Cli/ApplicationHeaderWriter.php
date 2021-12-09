<?php

declare(strict_types=1);

namespace ComposerRequireChecker\Cli;

use Symfony\Component\Console\Application as AbstractApplication;
use Symfony\Component\Console\Output\OutputInterface;

final class ApplicationHeaderWriter
{
    private ?AbstractApplication $application;

    public function __construct(?AbstractApplication $application = null)
    {
        $this->application = $application;
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
