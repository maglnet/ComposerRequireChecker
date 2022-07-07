<?php

declare(strict_types=1);

namespace ComposerRequireCheckerTest\DependencyGuesser;

use ComposerRequireChecker\DependencyGuesser\GuessFromInstalledComposerPackages;
use PHPUnit\Framework\TestCase;

use function dirname;
use function iterator_to_array;

final class GuessFromInstalledComposerPackagesTest extends TestCase
{
    private GuessFromInstalledComposerPackages $guesser;

    protected function setUp(): void
    {
        $this->guesser = new GuessFromInstalledComposerPackages(dirname(__DIR__, 3));
    }

    public function testGuessVendorClass(): void
    {
        $result = iterator_to_array(($this->guesser)(TestCase::class));

        self::assertNotEmpty($result);
        self::assertContains('phpunit/phpunit', $result);
    }

    public function testGuessVendorFunction(): void
    {
        $result = iterator_to_array(($this->guesser)('DeepCopy\deep_copy'));

        self::assertNotEmpty($result);
        self::assertContains('myclabs/deep-copy', $result);
    }

    public function testDoNotGuessClassFromProject(): void
    {
        $result = iterator_to_array(($this->guesser)(GuessFromInstalledComposerPackages::class));

        self::assertEmpty($result);
    }
}
