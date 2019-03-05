<?php

namespace ComposerRequireChecker\DependencyGuesser;


use Composer\Autoload\ClassLoader;

class GuessFromComposerAutoloader implements GuesserInterface
{

    /**
     * @var ClassLoader
     */
    private $composerAutoloader;

    private $configVendorDir;

    public function __construct(string $composerJsonPath)
    {
        $composerJson = json_decode(file_get_contents($composerJsonPath), true);
        $this->configVendorDir = $this->normalizePath(dirname($composerJsonPath) . '/' . ($composerJson['config']['vendor-dir'] ?? 'vendor'));
        $this->composerAutoloader = include $this->configVendorDir . '/autoload.php';
    }

    public function __invoke(string $symbolName): \Generator
    {
        $fullFileName = $this->composerAutoloader->findFile(ltrim($symbolName, '\\/ '));
        if ($fullFileName) {
            $fileName = $this->normalizePath(ltrim(substr(realpath($fullFileName), strlen($this->configVendorDir)), '\\/'));
            $packageName = preg_replace('/^([^\/]+\/[^\/]+).*/', '$1', $fileName);
            yield $packageName;
        }
    }

    private function normalizePath(string $path): string
    {
        return str_replace('\\', '/', $path);
    }

}
