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
        parent::__construct('ComposerRequireChecker', $this->getPackageVersion());

        $check = new CheckCommand();
        $this->add($check);
        $this->setDefaultCommand($check->getName());
    }

    private function getPackageVersion(): string
    {
        $version = null;
        $pharFile = \Phar::running();
        if ($pharFile) {
            $metadata = (new \Phar($pharFile))->getMetadata();
            $version = $metadata['version'] ?? null;
        }

        if (!$version) {
            $pwd = getcwd();
            chdir(realpath(__DIR__ . '/../../../'));
            $gitVersion = @exec('git describe --tags --dirty=-dev --always 2>&1', $output, $returnValue);
            chdir($pwd);
            if ($returnValue === 0) {
                $version = $gitVersion;
            }
        }

        return $version ?? 'unknown-development';
    }

}