<?php

declare(strict_types=1);

namespace ComposerRequireCheckerTest\DependencyGuesser;

use ComposerRequireChecker\Cli\Options;
use ComposerRequireChecker\DependencyGuesser\DependencyGuesser;
use ComposerRequireChecker\DependencyGuesser\GuessFromLoadedExtensions;
use PHPUnit\Framework\TestCase;

use function extension_loaded;

final class DependencyGuesserTest extends TestCase
{
    private DependencyGuesser $guesser;

    public function testGuessExtJson(): void
    {
        if (! extension_loaded('json')) {
            $this->markTestSkipped('extension json is not available');
        }

        $guesser = new DependencyGuesser([new GuessFromLoadedExtensions()]);

        $result = $guesser->__invoke('json_decode');
        $this->assertNotEmpty($result);
        $this->assertContains('ext-json', $result);
    }

    public function testDoesNotSuggestAnything(): void
    {
        $guesser = new DependencyGuesser([new GuessFromLoadedExtensions()]);

        $result = $guesser->__invoke('an_hopefully_unique_unknown_symbol');
        $this->assertFalse($result->valid());
    }

    public function testCoreExtensionsResolvesToPHP(): void
    {
        $guesser = new DependencyGuesser([new GuessFromLoadedExtensions(new Options(['php-core-extensions' => ['SPL', 'something-else']]))]);

        $result = $guesser->__invoke('RecursiveDirectoryIterator');
        $this->assertNotEmpty($result);
        $this->assertContains('php', $result);
    }
}
