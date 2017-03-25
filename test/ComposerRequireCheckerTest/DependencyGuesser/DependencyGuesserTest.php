<?php

namespace ComposerRequireCheckerTest\DependencyGuesser;


use ComposerRequireChecker\DependencyGuesser\DependencyGuesser;
use PHPUnit\Framework\TestCase;

class DependencyGuesserTest extends TestCase
{

    /**
     * @var DependencyGuesser
     */
    private $guesser;

    public function setUp()
    {
        $this->guesser = new DependencyGuesser();
    }

    public function testGuessExtJson()
    {
        if(!extension_loaded('json')) {
            $this->markTestSkipped('extension json is not available');
        }
        $result = $this->guesser->__invoke('json_decode');
        $this->assertNotEmpty($result);
        $this->assertContains('ext-json', $result);
    }

    public function testDoesNotSuggestAnything()
    {
        $result = $this->guesser->__invoke('an_hopefully_unique_unknown_symbol');
        $this->assertFalse($result->valid());
    }

}
