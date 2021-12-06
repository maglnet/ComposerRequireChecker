<?php

declare(strict_types=1);

namespace ComposerRequireChecker\Cli\ResultsWriter;

use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Output\OutputInterface;

use function count;
use function implode;

final class CliText implements ResultsWriter
{
    private OutputInterface $output;

    public function __construct(OutputInterface $output)
    {
        $this->output = $output;
    }

    /**
     * {@inheritdoc}
     */
    public function write(array $unknownSymbols): void
    {
        if (! $unknownSymbols) {
            $this->output->writeln('There were no unknown symbols found.');

            return;
        }

        $this->output->writeln('The following ' . count($unknownSymbols) . ' unknown symbols were found:');
        $table = new Table($this->output);
        $table->setHeaders(['Unknown Symbol', 'Guessed Dependency']);
        foreach ($unknownSymbols as $unknownSymbol => $guessedDependencies) {
            $table->addRow([$unknownSymbol, implode("\n", $guessedDependencies)]);
        }

        $table->render();
    }
}
