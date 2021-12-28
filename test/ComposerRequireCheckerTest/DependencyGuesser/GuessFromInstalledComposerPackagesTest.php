<?php

declare(strict_types=1);

namespace ComposerRequireCheckerTest\DependencyGuesser;

use ComposerRequireChecker\DependencyGuesser\GuessFromInstalledComposerPackages;
use PHPUnit\Framework\TestCase;

final class GuessFromInstalledComposerPackagesTest extends TestCase
{
    private GuessFromInstalledComposerPackages $guesser;

    protected function setUp(): void
    {
        $this->guesser = new GuessFromInstalledComposerPackages(dirname(__DIR__, 3));
    }

    public function testGuessVendorClass(): void
    {
        $result = ($this->guesser)(TestCase::class);

        self::assertNotEmpty($result);
        self::assertContains('phpunit/phpunit', $result);
    }

    public function testDoNotGuessVendorFunction(): void
    {
        $result = iterator_to_array(($this->guesser)('DeepCopy\deep_copy'));

        self::assertEmpty($result);
    }


    public function testDoNotGuessClassFromProject(): void
    {
        $result = iterator_to_array(($this->guesser)(GuessFromInstalledComposerPackages::class));

        self::assertEmpty($result);
    }
}