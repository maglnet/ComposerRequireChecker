<?php declare(strict_types=1);

namespace ComposerRequireCheckerTest\DependencyGuesser;

use ComposerRequireChecker\DependencyGuesser\GuessFromComposerInstalledJson;
use PHPUnit\Framework\TestCase;

class GuessFromComposerInstalledJsonTest extends TestCase
{
    /**
     * @var GuessFromComposerInstalledJson
     */
    private $guesser;

    protected function setUp(): void
    {
        $this->guesser = new GuessFromComposerInstalledJson(dirname(__DIR__, 3));
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
        $result = iterator_to_array(($this->guesser)(GuessFromComposerInstalledJson::class));

        self::assertEmpty($result);
    }
}
