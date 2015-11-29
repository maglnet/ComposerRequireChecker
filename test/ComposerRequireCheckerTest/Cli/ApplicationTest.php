<?php

namespace ComposerRequireCheckerTest\Cli;


use ComposerRequireChecker\Cli\Application;
use Symfony\Component\Console\Command\Command;

class ApplicationTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @var Application
     */
    private $application;

    public function setUp()
    {
        $this->application = new Application();
    }

    public function testCheckCommandExists()
    {
        $this->assertTrue($this->application->has('check'));
        $this->assertInstanceOf(Command::class, $this->application->get('check'));
    }

}