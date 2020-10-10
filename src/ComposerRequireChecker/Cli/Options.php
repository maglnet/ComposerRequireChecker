<?php

declare(strict_types=1);

namespace ComposerRequireChecker\Cli;

use InvalidArgumentException;

use function method_exists;
use function str_replace;
use function ucfirst;
use function ucwords;

class Options
{
    private $symbolWhitelist = [
        'null',
        'true',
        'false', // consts
        'static',
        'self',
        'parent', // class hierarchy
        'array',
        'string',
        'int',
        'float',
        'bool',
        'iterable',
        'callable',
        'void',
        'object', // types
    ];

    private $phpCoreExtensions = [
        'Core',
        'date',
        'pcre',
        'Phar',
        'Reflection',
        'SPL',
        'standard',
    ];

    /**
     * @see https://github.com/webmozart/glob
     *
     * @var string[] a list of glob patterns for files that should be scanned in addition
     */
    private array $scanFiles = [];

    public function __construct(array $options = [])
    {
        foreach ($options as $key => $option) {
            $methodName = 'set' . $this->getCamelCase($key);
            if (! method_exists($this, $methodName)) {
                throw new InvalidArgumentException(
                    $key . ' is not a known option - there is no method ' . $methodName
                );
            }

            $this->$methodName($option);
        }
    }

    /**
     * @return array
     */
    public function getSymbolWhitelist(): array
    {
        return $this->symbolWhitelist;
    }

    /**
     * @return string[]
     */
    public function getPhpCoreExtensions(): array
    {
        return $this->phpCoreExtensions;
    }

    /**
     * @param array $symbolWhitelist
     */
    public function setSymbolWhitelist(array $symbolWhitelist): void
    {
        $this->symbolWhitelist = $symbolWhitelist;
    }

    /**
     * @param array $phpCoreExtensions
     */
    public function setPhpCoreExtensions(array $phpCoreExtensions): void
    {
        $this->phpCoreExtensions = $phpCoreExtensions;
    }

    /**
     * @return string[] a list of glob patterns for files that should be scanned in addition
     */
    public function getScanFiles(): array
    {
        return $this->scanFiles;
    }

    /**
     * @param string[] $scanFiles a list of glob patterns for files that should be scanned in addition
     */
    public function setScanFiles(array $scanFiles): void
    {
        $this->scanFiles = $scanFiles;
    }

    /**
     * @param  string $string some-string
     *
     * @return string someString
     */
    private function getCamelCase(string $string): string
    {
        return ucfirst(str_replace(' ', '', ucwords(str_replace('-', ' ', $string))));
    }
}
