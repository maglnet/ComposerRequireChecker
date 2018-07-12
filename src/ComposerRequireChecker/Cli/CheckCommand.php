<?php

namespace ComposerRequireChecker\Cli;

use ComposerRequireChecker\ASTLocator\LocateASTFromFiles;
use ComposerRequireChecker\DefinedExtensionsResolver\DefinedExtensionsResolver;
use ComposerRequireChecker\DefinedSymbolsLocator\LocateDefinedSymbolsFromASTRoots;
use ComposerRequireChecker\DefinedSymbolsLocator\LocateDefinedSymbolsFromExtensions;
use ComposerRequireChecker\DependencyGuesser\DependencyGuesser;
use ComposerRequireChecker\FileLocator\LocateComposerPackageDirectDependenciesSourceFiles;
use ComposerRequireChecker\FileLocator\LocateComposerPackageSourceFiles;
use ComposerRequireChecker\GeneratorUtil\ComposeGenerators;
use ComposerRequireChecker\JsonLoader;
use ComposerRequireChecker\UsedSymbolsLocator\LocateUsedSymbolsFromASTRoots;
use PhpParser\ErrorHandler\Collecting as CollectingErrorHandler;
use PhpParser\ParserFactory;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class CheckCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('check')
            ->setDescription('check the defined dependencies against your code')
            ->addOption(
                'config-file',
                null,
                InputOption::VALUE_REQUIRED,
                'the config.json file to configure the checking options'
            )
            ->addArgument(
                'composer-json',
                InputArgument::OPTIONAL,
                'the composer.json of your package, that should be checked',
                './composer.json'
            )
            ->addOption(
                'ignore-parse-errors',
                null,
                InputOption::VALUE_NONE,
                'this will cause ComposerRequireChecker to ignore errors when files cannot be parsed, otherwise'
                . ' errors will be thrown'
            )
            ->addOption(
                'register-namespace',
                null,
                InputOption::VALUE_IS_ARRAY | InputOption::VALUE_OPTIONAL,
                'vendor/package:namespace:path as if it was psr4'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if (!$output->isQuiet()) {
            $output->writeln($this->getApplication()->getLongVersion());
        }

        $composerJson = realpath($input->getArgument('composer-json'));
        if (false === $composerJson) {
            throw new \InvalidArgumentException('file not found: [' . $input->getArgument('composer-json') . ']');
        }
        $this->checkJsonFile($composerJson);

        $options = $this->getCheckOptions($input);

        $getPackageSourceFiles = new LocateComposerPackageSourceFiles();
        $manualRegistered = $this->buildManualList($input, $composerJson);

        $sourcesASTs = $this->getASTFromFilesLocator($input);

        $definedVendorSymbols = (new LocateDefinedSymbolsFromASTRoots())->__invoke($sourcesASTs(
            (new ComposeGenerators())->__invoke(
                $getPackageSourceFiles($composerJson),
                (new LocateComposerPackageDirectDependenciesSourceFiles())->__invoke($composerJson, $manualRegistered)
            )
        ));

        $definedExtensionSymbols = (new LocateDefinedSymbolsFromExtensions())->__invoke(
            (new DefinedExtensionsResolver())->__invoke($composerJson, $options->getPhpCoreExtensions())
        );

        $usedSymbols = (new LocateUsedSymbolsFromASTRoots())
            ->__invoke($sourcesASTs($getPackageSourceFiles($composerJson)));

        if (!count($usedSymbols)) {
            throw new \LogicException('There were no symbols found, please check your configuration.');
        }

        $unknownSymbols = array_diff(
            $usedSymbols,
            $definedVendorSymbols,
            $definedExtensionSymbols,
            $options->getSymbolWhitelist()
        );

        if (!$unknownSymbols) {
            $output->writeln("There were no unknown symbols found.");
            return 0;
        }

        $output->writeln("The following unknown symbols were found:");
        $table = new Table($output);
        $table->setHeaders(['unknown symbol', 'guessed dependency']);
        $guesser = new DependencyGuesser();
        foreach ($unknownSymbols as $unknownSymbol) {
            $guessedDependencies = [];
            foreach ($guesser($unknownSymbol) as $guessedDependency) {
                $guessedDependencies[] = $guessedDependency;
            }
            $table->addRow([$unknownSymbol, implode("\n", $guessedDependencies)]);
        }
        $table->render();

        return ((int)(bool)$unknownSymbols);
    }

    private function getCheckOptions(InputInterface $input): Options
    {
        $fileName = $input->getOption('config-file');
        if (!$fileName) {
            return new Options();
        }
        return new Options((new JsonLoader($fileName))->getData());
    }

    /**
     * @param string $jsonFile
     * @throws \ComposerRequireChecker\Exception\InvalidJsonException
     * @throws \ComposerRequireChecker\Exception\NotReadableException
     * @internal param string $composerJson the path to composer.json
     */
    private function checkJsonFile(string $jsonFile)
    {
        // JsonLoader throws an exception if it cannot load the file
        new JsonLoader($jsonFile);
    }

    /**
     * @param InputInterface $input
     * @return LocateASTFromFiles
     */
    private function getASTFromFilesLocator(InputInterface $input): LocateASTFromFiles
    {
        $errorHandler = $input->getOption('ignore-parse-errors') ? new CollectingErrorHandler() : null;
        $sourcesASTs = new LocateASTFromFiles((new ParserFactory())->create(ParserFactory::PREFER_PHP7), $errorHandler);
        return $sourcesASTs;
    }

    /**
     * @return array
     */
    private function buildManualList(InputInterface $input, $composerJson)
    {
        if (!$input->hasOption('register-namespace')) {
            return [];
        }
        $packageDir = dirname($composerJson);
        $namespaces = [];
        foreach ($input->getOption('register-namespace') as $option) {
            if (preg_match('!^([^/:]+(/[^/:]+)+):([^:]+):([^:]+)$!i', $option, $matches)) {
                $namespaces[$packageDir . '/vendor/' . $matches[1]][$matches[3]] = $matches[4];
            }
        }
        return $namespaces;
    }
}
