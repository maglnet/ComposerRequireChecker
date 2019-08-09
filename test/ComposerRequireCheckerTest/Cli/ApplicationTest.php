<?php

namespace ComposerRequireCheckerTest\Cli;

use ComposerRequireChecker\Cli\Application;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;

class ApplicationTest extends TestCase
{

    /**
     * @var Application
     */
    private $application;

    public function setUp(): void
    {
        $this->application = new Application();
    }

    public function testCheckCommandExists(): void
    {
        $this->assertTrue($this->application->has('check'));
        $this->assertInstanceOf(Command::class, $this->application->get('check'));
    }
}
