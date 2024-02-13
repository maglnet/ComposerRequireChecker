<?php

declare(strict_types=1);

namespace ComposerRequireCheckerTest\Cli;

use ComposerRequireChecker\Cli\Application;
use InvalidArgumentException;
use LogicException;
use PHPUnit\Framework\TestCase;
use Spatie\TemporaryDirectory\TemporaryDirectory;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Tester\CommandTester;

use function chdir;
use function dirname;
use function file_put_contents;
use function json_decode;
use function unlink;
use function version_compare;

use const JSON_THROW_ON_ERROR;
use const PHP_EOL;
use const PHP_VERSION;

final class CheckCommandTest extends TestCase
{
    private CommandTester $commandTester;

    protected function setUp(): void
    {
        $application = new Application();
        $command     = $application->get('check');

        $this->commandTester = new CommandTester($command);
    }

    public function testExceptionIfComposerJsonIsNotAString(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->commandTester->execute([
            'composer-json' => ['this-is-a-array-as-input'],
        ]);
    }

    public function testExceptionIfComposerJsonNotFound(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->commandTester->execute(['composer-json' => 'this-will-not-be-found.json']);
    }

    public function testExceptionIfNoSymbolsFound(): void
    {
        $this->expectException(LogicException::class);

        $this->commandTester->execute([
            'composer-json' => dirname(__DIR__, 2) . '/fixtures/noSymbols/composer.json',
        ]);
    }

    public function testUnknownSymbolsFound(): void
    {
        $this->commandTester->execute([
            'composer-json' => dirname(__DIR__, 2) . '/fixtures/unknownSymbols/composer.json',
        ]);

        $this->assertSame(Command::FAILURE, $this->commandTester->getStatusCode());
        $display = $this->commandTester->getDisplay();

        $this->assertStringContainsString('The following 7 unknown symbols were found:', $display);
        $this->assertStringContainsString('Doctrine\Common\Collections\ArrayCollection', $display);
        $this->assertStringContainsString('Example\Library\Dependency', $display);
        $this->assertStringContainsString('FILTER_VALIDATE_URL', $display);
        $this->assertStringContainsString('filter_var', $display);
        $this->assertStringContainsString('Foo\Bar\Baz', $display);
        $this->assertStringContainsString('libxml_clear_errors', $display);
        $this->assertStringContainsString('Vendor\UnknownAttribute ', $display);
    }

    public function testInvalidOutputOptionValue(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Option "output" must be either of value "json", "text" or omitted altogether');

        $this->commandTester->execute([
            'composer-json' => dirname(__DIR__, 2) . '/fixtures/unknownSymbols/composer.json',
            '--output' => '__invalid__',
        ]);
    }

    public function testUnknownSymbolsFoundJsonReport(): void
    {
        $this->commandTester->execute([
            'composer-json' => dirname(__DIR__, 2) . '/fixtures/unknownSymbols/composer.json',
            '--output' => 'json',
        ]);

        $this->assertSame(Command::FAILURE, $this->commandTester->getStatusCode());
        $display = $this->commandTester->getDisplay();

        /** @var array{'unknown-symbols': array<array-key, list<string>>} $actual */
        $actual = json_decode($display, true, JSON_THROW_ON_ERROR);

        $this->assertSame(
            [
                'Doctrine\Common\Collections\ArrayCollection' => [],
                'Example\Library\Dependency' => [],
                'FILTER_VALIDATE_URL' => ['ext-filter'],
                'filter_var' => ['ext-filter'],
                'Foo\Bar\Baz' => [],
                'libxml_clear_errors' => ['ext-libxml'],
                'Vendor\UnknownAttribute' => [],
            ],
            $actual['unknown-symbols'],
        );
        self::assertStringEndsNotWith(PHP_EOL, $display);
    }

    public function testUnknownSymbolsFoundTextReport(): void
    {
        $this->commandTester->execute([
            'composer-json' => dirname(__DIR__, 2) . '/fixtures/unknownSymbols/composer.json',
            '--output' => 'text',
        ]);

        $this->assertSame(Command::FAILURE, $this->commandTester->getStatusCode());
        $display = $this->commandTester->getDisplay();

        $this->assertStringNotContainsString('The following 6 unknown symbols were found:', $display);
        $this->assertStringContainsString('Doctrine\Common\Collections\ArrayCollection', $display);
        $this->assertStringContainsString('Example\Library\Dependency', $display);
        $this->assertStringContainsString('FILTER_VALIDATE_URL', $display);
        $this->assertStringContainsString('filter_var', $display);
        $this->assertStringContainsString('Foo\Bar\Baz', $display);
        $this->assertStringContainsString('libxml_clear_errors', $display);
        $this->assertStringEndsNotWith(PHP_EOL . PHP_EOL, $display);
    }

    public function testSelfCheckShowsNoErrors(): void
    {
        $this->commandTester->execute([
            // that's our own composer.json, lets be sure our self check does not throw errors
            'composer-json' => dirname(__DIR__, 3) . '/composer.json',
        ]);

        $this->assertSame(0, $this->commandTester->getStatusCode());
        $display = $this->commandTester->getDisplay();
        $this->assertStringContainsString('no unknown symbols found', $display);

        // verbose output should not be shown
        $this->assertDoesNotMatchRegularExpression(
            '/Collecting defined (vendor|extension) symbols... found \d+ symbols./',
            $display,
        );
        $this->assertDoesNotMatchRegularExpression('/Collecting used symbols... found \d+ symbols./', $display);
    }

    public function testVerboseSelfCheckShowsCounts(): void
    {
        $this->commandTester->execute([
            // that's our own composer.json
            'composer-json' => dirname(__DIR__, 3) . '/composer.json',
        ], [
            'verbosity' => OutputInterface::VERBOSITY_VERBOSE,
        ]);

        $display = $this->commandTester->getDisplay();
        $this->assertMatchesRegularExpression('/Collecting defined vendor symbols... found \d+ symbols./', $display);
        $this->assertMatchesRegularExpression('/Collecting defined extension symbols... found \d+ symbols./', $display);
        $this->assertMatchesRegularExpression('/Collecting used symbols... found \d+ symbols./', $display);
    }

    public function testWithAdditionalSourceFiles(): void
    {
        $root = (new TemporaryDirectory())
            ->deleteWhenDestroyed()
            ->create();

        $configFilePath = $root->path('config.json');
        file_put_contents(
            $configFilePath,
            <<<'JSON'
{
    "scan-files": ["src/ComposerRequireChecker/Cli/CheckCommand.php"]
}
JSON
            ,
        );

        $this->commandTester->execute([
            // that's our own composer.json
            'composer-json' => dirname(__DIR__, 3) . '/composer.json',
            '--config-file' => $configFilePath,
        ]);

        $this->assertMatchesRegularExpression(
            '/There were no unknown symbols found./',
            $this->commandTester->getDisplay(),
        );
    }

    public function testSourceFileThatUsesDevDependency(): void
    {
        $root = (new TemporaryDirectory())
            ->deleteWhenDestroyed()
            ->create();

        $configFilePath = $root->path('config.json');
        file_put_contents($configFilePath, '{"scan-files":["test/ComposerRequireCheckerTest/Cli/CheckCommandTest.php"]}');

        $exitCode = $this->commandTester->execute([
            // that's our own composer.json
            'composer-json' => dirname(__DIR__, 3) . '/composer.json',
            '--config-file' => $configFilePath,
        ]);

        $this->assertNotEquals(0, $exitCode);
        $this->assertMatchesRegularExpression(
            '/The following 2 unknown symbols were found.*PHPUnit\\\\Framework\\\\TestCase/s',
            $this->commandTester->getDisplay(),
        );
    }

    public function testNoUnknownSymbolsFound(): void
    {
        $this->commandTester->execute([
            'composer-json' => dirname(__DIR__, 2) . '/fixtures/noUnknownSymbols/composer.json',
        ]);

        self::assertSame(Command::SUCCESS, $this->commandTester->getStatusCode());
        self::assertStringContainsString(
            'There were no unknown symbols found.',
            $this->commandTester->getDisplay(),
        );
    }

    public function testReservedKeywordInPhp8DoesNotThrowExceptionInPhp7(): void
    {
        if (version_compare(PHP_VERSION, '8.0.0') >= 0) {
            self::markTestSkipped('This test does not work in PHP8');
        }

        $baseDir = dirname(__DIR__, 2) . '/fixtures/noUnknownSymbols/';
        $tmpFile = $baseDir . 'src/Match.php';
        file_put_contents($tmpFile, '<?php class Match { }');

        $this->commandTester->execute(['composer-json' => $baseDir . 'composer.json']);

        unlink($tmpFile);
        self::assertSame(Command::SUCCESS, $this->commandTester->getStatusCode());
    }

    public function testUnknownComposerSymbolFound(): void
    {
        $this->commandTester->execute([
            'composer-json' => dirname(__DIR__, 2) . '/fixtures/unknownComposerSymbol/composer.json',
        ]);

        $this->assertSame(Command::FAILURE, $this->commandTester->getStatusCode());
        $display = $this->commandTester->getDisplay();

        $this->assertStringContainsString('The following 1 unknown symbols were found:', $display);
        $this->assertStringContainsString('Composer\InstalledVersions', $display);
    }

    public function testNoUnknownComposerSymbolFound(): void
    {
        $this->commandTester->execute([
            'composer-json' => dirname(__DIR__, 2) . '/fixtures/noUnknownComposerSymbol/composer.json',
        ]);

        self::assertSame(Command::SUCCESS, $this->commandTester->getStatusCode());
        self::assertStringContainsString(
            'There were no unknown symbols found.',
            $this->commandTester->getDisplay(),
        );
    }

    /** This is the same as the next test, but with the default configuration file assumed */
    public function testDefaultConfigPath(): void
    {
        $baseDirectory = dirname(__DIR__, 2) . '/fixtures/defaultConfigPath/';

        chdir($baseDirectory);
        $exitCode = $this->commandTester->execute(['composer-json' => 'composer.json']);
        $output   = $this->commandTester->getDisplay();

        $this->assertNotEquals(0, $exitCode);
        $this->assertMatchesRegularExpression(
            '/The following 2 unknown symbols were found/s',
            $output,
        );
        $this->assertMatchesRegularExpression(
            '/Composer\\\\InstalledVersions/s',
            $output,
        );
        $this->assertMatchesRegularExpression(
            '/json_decode/s',
            $output,
        );
    }

    /** This is the same as the previous test, but with the configuration file specified */
    public function testDefaultConfigPathSpecified(): void
    {
        $baseDirectory = dirname(__DIR__, 2) . '/fixtures/defaultConfigPath/';

        chdir($baseDirectory);
        $exitCode = $this->commandTester->execute([
            'composer-json' => 'composer.json',
            '--config-file' => 'composer-require-checker.json',
        ]);
        $output   = $this->commandTester->getDisplay();

        $this->assertNotEquals(0, $exitCode);
        $this->assertMatchesRegularExpression(
            '/The following 2 unknown symbols were found/s',
            $output,
        );
        $this->assertMatchesRegularExpression(
            '/Composer\\\\InstalledVersions/s',
            $output,
        );
        $this->assertMatchesRegularExpression(
            '/json_decode/s',
            $output,
        );
    }

    public function testOverrideDefaultConfigPath(): void
    {
        $baseDirectory = dirname(__DIR__, 2) . '/fixtures/defaultConfigPath/';
        $root          = (new TemporaryDirectory())
            ->deleteWhenDestroyed()
            ->create();

        $configFilePath = $root->path('config.json');
        file_put_contents($configFilePath, '{"scan-files":[]}');

        chdir($baseDirectory);
        $exitCode = $this->commandTester->execute([
            'composer-json' => 'composer.json',
            '--config-file' => $configFilePath,
        ]);

        $output = $this->commandTester->getDisplay();

        $this->assertNotEquals(0, $exitCode);
        $this->assertMatchesRegularExpression(
            '/The following 1 unknown symbols were found/s',
            $output,
        );
        $this->assertMatchesRegularExpression(
            '/Composer\\\\InstalledVersions/s',
            $output,
        );
        $this->assertDoesNotMatchRegularExpression(
            '/json_decode/s',
            $output,
        );
    }

    public function testNotExistentConfigPath(): void
    {
        $baseDirectory = dirname(__DIR__, 2) . '/fixtures/defaultConfigPath/';

        chdir($baseDirectory);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Configuration file [not-existent-config.json] does not exist.');
        $this->commandTester->execute([
            'composer-json' => 'composer.json',
            '--config-file' => 'not-existent-config.json',
        ]);
    }

    public function testNotExistentDefaultConfigPath(): void
    {
        $baseDirectory = dirname(__DIR__, 2) . '/fixtures/noUnknownSymbols/';

        chdir($baseDirectory);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Configuration file [composer-require-checker.json] does not exist.');
        $this->commandTester->execute([
            'composer-json' => 'composer.json',
            '--config-file' => 'composer-require-checker.json',
        ]);
    }

    /** @requires PHP >= 8.1.0 */
    public function testNoUnknownEnumSymbolsFound(): void
    {
        $this->commandTester->execute([
            'composer-json' => dirname(__DIR__, 2) . '/fixtures/noUnknownEnumSymbols/composer.json',
        ]);

        self::assertSame(Command::SUCCESS, $this->commandTester->getStatusCode());
        self::assertStringContainsString(
            'There were no unknown symbols found.',
            $this->commandTester->getDisplay(),
        );
    }

    public function testNoUnknownAttributeSymbolsFound(): void
    {
        $this->commandTester->execute([
            'composer-json' => dirname(__DIR__, 2) . '/fixtures/noUnknownAttributeSymbols/composer.json',
        ]);

        self::assertSame(Command::SUCCESS, $this->commandTester->getStatusCode());
        self::assertStringContainsString(
            'There were no unknown symbols found.',
            $this->commandTester->getDisplay(),
        );
    }
}
