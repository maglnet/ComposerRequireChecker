<?php

namespace ComposerRequireCheckerTest\Cli;

use ComposerRequireChecker\Cli\CheckCommand;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;
use Symfony\Component\Console\Input\InputInterface;

class CheckCommandGetManualListTest extends TestCase
{
    /**
     * @return array
     */
    public function provideBuildManualList():array
    {
        $method = (new ReflectionClass(CheckCommand::class))->getMethod('buildManualList');
        $method->setAccessible(true);
        return [
            [$this->getInputMockForManualList(), $method, []],
            [$this->getInputMockForManualList(
                ['abc', 'nope', 'abc/hi:Abc\\:src', 'abc/def/qoi:Qui\\:src/lib/ext', 'abc/def/:Qui\\:src/lib/ext']),
                $method,
                [__DIR__.'/vendor/abc/def/qoi', __DIR__.'/vendor/abc/hi']
            ],
        ];
    }

    /**
     * @param array $return
     * @return InputInterface
     */
    private function getInputMockForManualList(array $return = [])
    {
        $input = $this->getMockBuilder(InputInterface::class)->getMock();
        $hasReturn = count($return)>0;
        $input->expects($this->once())
            ->method('hasOption')
            ->with('register-namespace')
            ->willReturn($hasReturn);
        $input->expects($hasReturn?$this->once():$this->never())
            ->method('getOption')
            ->with('register-namespace')
            ->willReturn($return);
        return $input;
    }

    /**
     * @dataProvider provideBuildManualList
     * Since the command does so much, there's no reasonable way to supply test data
     * @test
     */
    public function testBuildManualList(InputInterface $input, ReflectionMethod $method, array $expected)
    {
        $instance = new CheckCommand();
        $result = $method->invoke($instance, $input, __FILE__);
        $this->assertCount(count($expected), $result);
        $this->assertCount(0, array_diff(array_keys($result), $expected));
    }
}