<?php

namespace ComposerRequireCheckerTest\DependencyGuesser;


use ComposerRequireChecker\DependencyGuesser\GuessFromComposerAutoloader;
use PhpParser\ParserFactory;
use PHPUnit\Framework\TestCase;

class GuessFromComposerAutoloaderTest extends TestCase
{

    /**
     * @var GuessFromComposerAutoloader
     */
    private $guesser;

    public function setUp()
    {
        parent::setUp();
        $dir = dirname(__DIR__, 3);
        $this->guesser = new GuessFromComposerAutoloader($dir . '/composer.json');
    }

    public function testClassWillBeFound()
    {
        $quessedDependencies = $this->guesser->__invoke(ParserFactory::class);
        $guessedDependencies = iterator_to_array($quessedDependencies);

        $this->assertCount(1, $guessedDependencies);
        $this->assertContains('nikic/php-parser', $guessedDependencies);
    }
}
