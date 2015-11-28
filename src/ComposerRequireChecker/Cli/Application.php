<?php
/**
 * Created by PhpStorm.
 * User: matthias
 * Date: 28.11.15
 * Time: 23:49
 */

namespace ComposerRequireChecker\Cli;
use Symfony\Component\Console\Application as AbstractApplication;

class Application extends AbstractApplication
{

    public function __construct()
    {
        parent::__construct('ComposerRequireChecker', 'dev');

        $check = new CheckCommand();
        $this->add($check);
        $this->setDefaultCommand($check->getName());
    }

}