<?php

namespace ComposerRequireChecker\Cli;

use ComposerRequireChecker\ASTLocator\LocateASTFromFiles;
use ComposerRequireChecker\DefinedExtensionsResolver\DefinedExtensionsResolver;
use ComposerRequireChecker\DefinedSymbolsLocator\LocateDefinedSymbolsFromExtensions;
use ComposerRequireChecker\DependencyGuesser\DependencyGuesser;
use ComposerRequireChecker\Exception\DependenciesNotInstalledException;
use ComposerRequireChecker\FileLocator\LocateComposerPackageSourceFiles;
use ComposerRequireChecker\FileLocator\LocateFilesByGlobPattern;
use ComposerRequireChecker\GeneratorUtil\ComposeGenerators;
use ComposerRequireChecker\JsonLoader;
use ComposerRequireChecker\UsedSymbolsLocator\LocateUsedSymbolsFromASTRoots;
use PhpParser\ErrorHandler\Collecting as CollectingErrorHandler;
use PhpParser\ParserFactory;
use Roave\BetterReflection\BetterReflection;
use Roave\BetterReflection\Reflector\ClassReflector;
use Roave\BetterReflection\Reflector\ConstantReflector;
use Roave\BetterReflection\Reflector\Exception\IdentifierNotFound;
use Roave\BetterReflection\Reflector\FunctionReflector;
use Roave\BetterReflection\SourceLocator\Type\Composer\Factory\Exception\MissingComposerJson;
use Roave\BetterReflection\SourceLocator\Type\Composer\Factory\MakeLocatorForComposerJsonAndInstalledJson;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use function dirname;

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
        $composerData = $this->getComposerData($composerJson);

        $options = $this->getCheckOptions($input);

        $getPackageSourceFiles = new LocateComposerPackageSourceFiles();
        $getAdditionalSourceFiles = new LocateFilesByGlobPattern();

        $sourcesASTs = $this->getASTFromFilesLocator($input);

        $this->verbose("Collecting used symbols... ", $output);
        $usedSymbols = (new LocateUsedSymbolsFromASTRoots())->__invoke($sourcesASTs(
            (new ComposeGenerators())->__invoke(
                $getPackageSourceFiles($composerData, dirname($composerJson)),
                $getAdditionalSourceFiles($options->getScanFiles(), dirname($composerJson))
            )
        ));
        $this->verbose("found " . count($usedSymbols) . " symbols.", $output, true);


        $this->verbose("Collecting defined extension symbols... ", $output);
        $definedExtensionSymbols = (new LocateDefinedSymbolsFromExtensions())->__invoke(
            (new DefinedExtensionsResolver())->__invoke($composerJson, $options->getPhpCoreExtensions())
        );
        $this->verbose("found " . count($definedExtensionSymbols) . " symbols.", $output, true);

        try {
            $locator = new MakeLocatorForComposerJsonAndInstalledJson();
            $astLocator = (new BetterReflection())->astLocator();
            $composerLocator = $locator(dirname($composerJson), $astLocator);
            $classReflector = new ClassReflector($composerLocator);
            $functionReflector = new FunctionReflector($composerLocator, $classReflector);
            $constantReflector = new ConstantReflector($composerLocator, $classReflector);
        } catch (MissingComposerJson $missingComposerJson) {
            $message = 'The composer dependencies have not been installed, run composer install/update first';
            throw new DependenciesNotInstalledException($message);
        }

        $whiteList = $options->getSymbolWhitelist();
        $unknownSymbols = [];
        foreach ($usedSymbols as $usedSymbol) {
            if (in_array($usedSymbol, $whiteList)) {
                continue;
            }

            if (in_array($usedSymbol, $definedExtensionSymbols)) {
                continue;
            }

            try {
                $classReflector->reflect($usedSymbol);

                continue;
            } catch (IdentifierNotFound $ignore) {
                // void
            }

            try {
                $functionReflector->reflect($usedSymbol);

                continue;
            } catch (IdentifierNotFound $ignore) {
                // void
            }

            try {
                $constantReflector->reflect($usedSymbol);

                continue;
            } catch (IdentifierNotFound $ignore) {
                // void
            }

            $unknownSymbols[] = $usedSymbol;
        }

        if (!$unknownSymbols) {
            $output->writeln("There were no unknown symbols found.");
            return 0;
        }

        $output->writeln("The following unknown symbols were found:");
        $table = new Table($output);
        $table->setHeaders(['unknown symbol', 'guessed dependency']);
        $guesser = new DependencyGuesser($options);
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
     */
    private function getComposerData(string $jsonFile): array
    {
        // JsonLoader throws an exception if it cannot load the file
        return (new JsonLoader($jsonFile))->getData();
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
     * @param string $string the message that should be printed
     * @param OutputInterface $output the output to log to
     * @param bool $newLine if a new line will be started afterwards
     */
    private function verbose(string $string, OutputInterface $output, bool $newLine = false): void
    {
        if (!$output->isVerbose()) {
            return;
        }

        $output->write($string, $newLine);
    }
}
