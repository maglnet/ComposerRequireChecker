<?php

declare(strict_types=1);

namespace ComposerRequireChecker\Cli;

use ComposerRequireChecker\ASTLocator\LocateASTFromFiles;
use ComposerRequireChecker\Cli\ResultsWriter\CliJson;
use ComposerRequireChecker\Cli\ResultsWriter\CliText;
use ComposerRequireChecker\DefinedExtensionsResolver\DefinedExtensionsResolver;
use ComposerRequireChecker\DefinedSymbolsLocator\LocateDefinedSymbolsFromASTRoots;
use ComposerRequireChecker\DefinedSymbolsLocator\LocateDefinedSymbolsFromComposerAutoload;
use ComposerRequireChecker\DefinedSymbolsLocator\LocateDefinedSymbolsFromComposerRuntimeApi;
use ComposerRequireChecker\DefinedSymbolsLocator\LocateDefinedSymbolsFromExtensions;
use ComposerRequireChecker\DependencyGuesser\DependencyGuesser;
use ComposerRequireChecker\Exception\InvalidJson;
use ComposerRequireChecker\FileLocator\LocateComposerPackageSourceFiles;
use ComposerRequireChecker\FileLocator\LocateFilesByGlobPattern;
use ComposerRequireChecker\GeneratorUtil\ComposeGenerators;
use ComposerRequireChecker\JsonLoader;
use ComposerRequireChecker\UsedSymbolsLocator\LocateUsedSymbolsFromASTRoots;
use DateTimeImmutable;
use InvalidArgumentException;
use LogicException;
use Override;
use PhpParser\ErrorHandler\Collecting as CollectingErrorHandler;
use PhpParser\ParserFactory;
use Psl\Filesystem;
use Psl\Type;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use function array_combine;
use function array_diff;
use function array_map;
use function array_merge;
use function count;
use function dirname;
use function gettype;
use function is_string;
use function sprintf;

/** @psalm-import-type ComposerData from JsonLoader */
class CheckCommand extends Command
{
    public const string NAME                 = 'check';
    private const string DEFAULT_CONFIG_PATH = 'composer-require-checker.json';

    #[Override]
    protected function configure(): void
    {
        $this
            ->setName(self::NAME)
            ->setDescription('check the defined dependencies against your code')
            ->addOption(
                'config-file',
                null,
                InputOption::VALUE_REQUIRED,
                'the config file to configure the checking options',
            )
            ->addArgument(
                'composer-json',
                InputArgument::OPTIONAL,
                'the composer.json of your package, that should be checked',
                './composer.json',
            )
            ->addOption(
                'ignore-parse-errors',
                null,
                InputOption::VALUE_NONE,
                'this will cause ComposerRequireChecker to ignore errors when files cannot be parsed, otherwise'
                . ' errors will be thrown',
            )
            ->addOption(
                'output',
                null,
                InputOption::VALUE_REQUIRED,
                'generate output either as "text" or as "json", if specified, "quiet mode" is implied',
            );
    }

    #[Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $option = Type\union(
                Type\null(),
                Type\literal_scalar('text'),
                Type\literal_scalar('json'),
            )->coerce($input->getOption('output'));
        } catch (Type\Exception\CoercionException $failedCoercion) {
            throw new InvalidArgumentException(
                'Option "output" must be either of value "json", "text" or omitted altogether',
                previous: $failedCoercion,
            );
        }

        if ($option !== null) {
            $output->setVerbosity(OutputInterface::VERBOSITY_QUIET);
        }

        (new ApplicationHeaderWriter($this->getApplication()))->__invoke($output);

        $composerJsonArgument = $input->getArgument('composer-json');

        if (is_string($composerJsonArgument) === false) {
            throw new InvalidArgumentException(sprintf(
                'composer-json must be type of string but %s given',
                gettype($composerJsonArgument),
            ));
        }

        $composerJson = Filesystem\canonicalize($composerJsonArgument);

        if ($composerJson === null) {
            throw new InvalidArgumentException(sprintf('file not found: [%s]', $composerJsonArgument));
        }

        $composerData = JsonLoader::getData($composerJson, JsonLoader::composerDataType());

        $options = $this->getCheckOptions($input);

        $getPackageSourceFiles    = new LocateComposerPackageSourceFiles();
        $getAdditionalSourceFiles = new LocateFilesByGlobPattern();

        $sourcesASTs = $this->getASTFromFilesLocator($input);

        $this->verbose('Collecting defined vendor symbols... ', $output);
        $definedVendorSymbols = array_merge(
            (new LocateDefinedSymbolsFromASTRoots())->__invoke(
                $sourcesASTs(
                    (new ComposeGenerators())->__invoke(
                        $getAdditionalSourceFiles($options->getScanFiles(), dirname($composerJson)),
                        $getPackageSourceFiles($composerData, dirname($composerJson)),
                    ),
                ),
            ),
            (new LocateDefinedSymbolsFromComposerAutoload())->__invoke($composerJson, $sourcesASTs),
            (new LocateDefinedSymbolsFromComposerRuntimeApi())->__invoke($composerData),
        );

        $this->verbose(sprintf('found %d symbols.', count($definedVendorSymbols)), $output, true);

        $this->verbose('Collecting defined extension symbols... ', $output);
        $definedExtensionSymbols = (new LocateDefinedSymbolsFromExtensions())->__invoke(
            (new DefinedExtensionsResolver())->__invoke($composerJson, $options->getPhpCoreExtensions()),
        );
        $this->verbose(sprintf('found %d symbols.', count($definedExtensionSymbols)), $output, true);

        $this->verbose('Collecting used symbols... ', $output);
        $usedSymbols = (new LocateUsedSymbolsFromASTRoots())->__invoke(
            $sourcesASTs(
                (new ComposeGenerators())->__invoke(
                    $getPackageSourceFiles($composerData, dirname($composerJson)),
                    $getAdditionalSourceFiles($options->getScanFiles(), dirname($composerJson)),
                ),
            ),
        );
        $this->verbose(sprintf('found %d symbols.', count($usedSymbols)), $output, true);

        if (! count($usedSymbols)) {
            throw new LogicException('There were no symbols found, please check your configuration.');
        }

        $this->verbose('Checking for unknown symbols... ', $output, true);
        $unknownSymbols = array_diff(
            $usedSymbols,
            $definedVendorSymbols,
            $definedExtensionSymbols,
            $options->getSymbolWhitelist(),
        );

        // pcov which is used for coverage does not detect executed code in anonymous functions used as callable
        // therefore we require to have closure class.
        $outputWrapper = new class ($output) {
            public function __construct(private OutputInterface $output)
            {
            }

            public function __invoke(string $string): void
            {
                $this->output->write($string, false, OutputInterface::VERBOSITY_QUIET);
            }
        };

        switch ($option) {
            case 'json':
                $application   = $this->getApplication();
                $resultsWriter = new CliJson(
                    $outputWrapper,
                    $application?->getVersion() ?? 'Unknown version',
                    static fn () => new DateTimeImmutable(),
                );
                break;
            case 'text':
                $resultsWriter = new CliText(
                    $output,
                    $outputWrapper,
                );
                break;
            default:
                $resultsWriter = new CliText($output);
        }

        $guesser = new DependencyGuesser($options);
        $resultsWriter->write(
            array_map(
                static function (string $unknownSymbol) use ($guesser): array {
                    $guessedDependencies = [];
                    foreach ($guesser($unknownSymbol) as $guessedDependency) {
                        $guessedDependencies[] = $guessedDependency;
                    }

                    return $guessedDependencies;
                },
                array_combine($unknownSymbols, $unknownSymbols),
            ),
        );

        return (int) (bool) $unknownSymbols;
    }

    /** @throws InvalidJson */
    private function getCheckOptions(InputInterface $input): Options
    {
        $configFileArgument = Type\union(Type\null(), Type\non_empty_string())
            ->coerce($input->getOption('config-file'));

        if (is_string($configFileArgument)) {
            $fileName = Filesystem\canonicalize($configFileArgument);
            if ($fileName === null) {
                throw new InvalidArgumentException(
                    sprintf(
                        'Configuration file [%s] does not exist.',
                        $configFileArgument,
                    ),
                );
            }
        } else {
            $fileName = Filesystem\canonicalize(self::DEFAULT_CONFIG_PATH);
            if ($fileName === null) {
                return new Options();
            }
        }

        return new Options(JsonLoader::getData(
            $fileName,
            Type\dict(Type\string(), Type\mixed()),
        ));
    }

    private function getASTFromFilesLocator(InputInterface $input): LocateASTFromFiles
    {
        $errorHandler = $input->getOption('ignore-parse-errors') ? new CollectingErrorHandler() : null;
        $parser       = (new ParserFactory())->createForNewestSupportedVersion();

        return new LocateASTFromFiles($parser, $errorHandler);
    }

    /**
     * @param string          $string  the message that should be printed
     * @param OutputInterface $output  the output to log to
     * @param bool            $newLine if a new line will be started afterwards
     */
    private function verbose(string $string, OutputInterface $output, bool $newLine = false): void
    {
        if (! $output->isVerbose()) {
            return;
        }

        $output->write($string, $newLine);
    }
}
