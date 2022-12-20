<?php

declare(strict_types=1);

namespace ComposerRequireChecker\Cli\ResultsWriter;

use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\OutputInterface;

use function count;
use function implode;

final class CliText implements ResultsWriter
{
    /** @var callable */
    private $writeCallable;

    public function __construct(private OutputInterface $output, callable|null $write = null)
    {
        if ($write === null) {
            $write = static function (string $string) use ($output): void {
                $output->write($string);
            };
        }

        $this->writeCallable = $write;
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

        $tableOutput = new BufferedOutput();
        $table       = new Table($tableOutput);
        $table->setHeaders(['Unknown Symbol', 'Guessed Dependency']);
        foreach ($unknownSymbols as $unknownSymbol => $guessedDependencies) {
            $table->addRow([$unknownSymbol, implode("\n", $guessedDependencies)]);
        }

        $table->render();

        $write = $this->writeCallable;
        $write($tableOutput->fetch());
    }
}
