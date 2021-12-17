<?php

declare(strict_types=1);

namespace ComposerRequireCheckerTest;

use ComposerRequireChecker\SymbolCache;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;

use function file_put_contents;
use function sys_get_temp_dir;
use function tempnam;

/**
 * @covers \ComposerRequireChecker\SymbolCache
 */
final class SymbolCacheTest extends TestCase
{
    private ArrayAdapter $cache;

    private SymbolCache $symbolCache;

    protected function setUp(): void
    {
        parent::setUp();

        $this->cache       = new ArrayAdapter();
        $this->symbolCache = new SymbolCache($this->cache);
    }

    public function testGetFromCache(): void
    {
        $file = tempnam(sys_get_temp_dir(), 'symbols');
        file_put_contents($file, 'version1');

        $this->assertSame(['1'], $this->symbolCache->__invoke($file, static fn () => ['1']));
        $this->assertSame(['1'], $this->symbolCache->__invoke($file, static fn () => ['2']));

        file_put_contents($file, 'version2');
        $this->assertSame(['2'], $this->symbolCache->__invoke($file, static fn () => ['2']));
    }
}
