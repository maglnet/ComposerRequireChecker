<?php

namespace ComposerRequireCheckerTest\DependencyGuesser;


use ComposerRequireChecker\DependencyGuesser\DependencyGuesser;
use ComposerRequireChecker\DependencyGuesser\GuessFromComposerAutoloader;
use PhpParser\ParserFactory;
use PHPUnit\Framework\TestCase;

class GuessFromComposerAutoloaderTest extends TestCase
{

    /**
     * @var DependencyGuesser
     */
    private $guesser;

    public function setUp()
    {
        $dir = dirname(__DIR__, 3);
        $this->guesser = new DependencyGuesser(new GuessFromComposerAutoloader($dir . '/composer.json'));
    }

    public function testClassWillBeFound()
    {
        $quessedDependencies = $this->guesser->__invoke(ParserFactory::class);
        $guessedDependencies = iterator_to_array($quessedDependencies);

        $this->assertCount(1, $guessedDependencies);
        $this->assertContains('nikic/php-parser', $guessedDependencies);
    }
}
