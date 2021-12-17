<?php

declare(strict_types=1);

namespace ComposerRequireChecker;

use Psr\Cache\InvalidArgumentException;
use Symfony\Contracts\Cache\CacheInterface;

use function sha1_file;

final class SymbolCache
{
    private CacheInterface $cache;

    public function __construct(CacheInterface $cache)
    {
        $this->cache = $cache;
    }

    /**
     * @param callable(): array<string> $callback
     *
     * @return array<string>
     *
     * @throws InvalidArgumentException
     */
    public function __invoke(string $file, callable $callback): array
    {
        return $this->cache->get($this->getKey($file), $callback);
    }

    private function getKey(string $file): string
    {
        return sha1_file($file);
    }
}
