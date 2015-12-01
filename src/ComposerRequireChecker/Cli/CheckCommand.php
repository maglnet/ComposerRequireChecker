<?php
/**
 * Created by PhpStorm.
 * User: matthias
 * Date: 28.11.15
 * Time: 16:16
 */

namespace ComposerRequireChecker\Cli;

use ComposerRequireChecker\ASTLocator\LocateASTFromFiles;
use ComposerRequireChecker\DefinedExtensionsResolver\DefinedExtensionsResolver;
use ComposerRequireChecker\DefinedSymbolsLocator\LocateDefinedSymbolsFromASTRoots;
use ComposerRequireChecker\DefinedSymbolsLocator\LocateDefinedSymbolsFromExtensions;
use ComposerRequireChecker\FileLocator\LocateComposerPackageDirectDependenciesSourceFiles;
use ComposerRequireChecker\FileLocator\LocateComposerPackageSourceFiles;
use ComposerRequireChecker\GeneratorUtil\ComposeGenerators;
use ComposerRequireChecker\UsedSymbolsLocator\LocateUsedSymbolsFromASTRoots;
use PhpParser\ParserFactory;
use Symfony\Component\Console\Command\Command;
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
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output) : int
    {

        $getPackageSourceFiles = new LocateComposerPackageSourceFiles();

        $sourcesASTs  = new LocateASTFromFiles((new ParserFactory())->create(ParserFactory::PREFER_PHP7));
        $composerJson = $input->getArgument('composer-json');

        $definedVendorSymbols = (new LocateDefinedSymbolsFromASTRoots())->__invoke($sourcesASTs(
            (new ComposeGenerators())->__invoke(
                $getPackageSourceFiles($composerJson),
                (new LocateComposerPackageDirectDependenciesSourceFiles())->__invoke($composerJson)
            )
        ));

        $options = $this->getCheckOptions($input);

        $definedExtensionSymbols = (new LocateDefinedSymbolsFromExtensions())->__invoke(
            (new DefinedExtensionsResolver())->__invoke($composerJson, $options->getPhpCoreExtensions())
        );

        $usedSymbols = (new LocateUsedSymbolsFromASTRoots())->__invoke($sourcesASTs($getPackageSourceFiles($composerJson)));

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
        foreach ($unknownSymbols as $unknownSymbol) {
            $output->writeln("  " . $unknownSymbol);
        }

        return ((int) (bool) $unknownSymbols);
    }

    private function getCheckOptions(InputInterface $input) : Options
    {
        $fileName = $input->getOption('config-file');
        if(!$fileName) {
            return new Options();
        }

        if(!is_readable($fileName)) {
            throw new \InvalidArgumentException('unable to read ' . $fileName);
        }

        $jsonData = json_decode(file_get_contents($fileName), true);
        if(false === $jsonData) {
            throw new \Exception('error parsing the config file: ' . json_last_error_msg());
        }

        return new Options($jsonData);

    }
}