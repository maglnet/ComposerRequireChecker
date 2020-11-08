<?php

declare(strict_types=1);

namespace ComposerRequireCheckerTest\DependencyGuesser;

use ComposerRequireChecker\Cli\Options;
use ComposerRequireChecker\DependencyGuesser\DependencyGuesser;
use PHPUnit\Framework\TestCase;

use function extension_loaded;

final class DependencyGuesserTest extends TestCase
{
    private DependencyGuesser $guesser;

    protected function setUp(): void
    {
        $this->guesser = new DependencyGuesser();
    }

    public function testGuessExtJson(): void
    {
        if (! extension_loaded('json')) {
            $this->markTestSkipped('extension json is not available');
        }

        $result = $this->guesser->__invoke('json_decode');
        $this->assertNotEmpty($result);
        $this->assertContains('ext-json', $result);
    }

    public function testDoesNotSuggestAnything(): void
    {
        $result = $this->guesser->__invoke('an_hopefully_unique_unknown_symbol');
        $this->assertFalse($result->valid());
    }

    public function testCoreExtensionsResolvesToPHP(): void
    {
        $options       = new Options(['php-core-extensions' => ['SPL', 'something-else']]);
        $this->guesser = new DependencyGuesser($options);
        $result        = $this->guesser->__invoke('RecursiveDirectoryIterator');
        $this->assertNotEmpty($result);
        $this->assertContains('php', $result);
    }
}
