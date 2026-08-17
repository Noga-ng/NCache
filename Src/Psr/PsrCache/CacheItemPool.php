<?php

declare(strict_types=1);

namespace NCache\Psr\PsrCache;

use NCache\Enum\CType;
use NCache\NCache;
use NCache\Psr\PsrCache\Exceptions\InvalidCacheArgumentException;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;

/**
 * @phpstan-type ItemData array<array-key,mixed>|string|int|bool|float|null|callable
 */
final class CacheItemPool implements CacheItemPoolInterface
{
    /**
     * @var array<string, CacheItemInterface>
     */
    private array $deferred = [];

    public function __construct(
        private readonly string $config,
        private readonly string $profile = '',
        private readonly ?CType $type = null,
    ) {
    }

    private function activate(): void
    {
        NCache::config(
            $this->config,
        )->use(
            $this->profile,
        );
    }

    private function cache(string $key): NCache
    {
        $this->activate();
        $this->validateKey($key);

        return NCache::key($key, $this->type);
    }

    public function getItem(string $key): CacheItemInterface
    {
        $cache = $this->cache($key);

        if (!$cache->has()) {
            return new PsrCacheItem($key, null, false);
        }

        return new PsrCacheItem(
            $key,
            $cache->get(),
            true,
        );
    }

    /**
     * @param string[] $keys
     * @return iterable<string,CacheItemInterface>
     */
    public function getItems(array $keys = []): iterable
    {
        $items = [];

        foreach ($keys as $key) {
            $items[$key] = $this->getItem($key);
        }

        return $items;
    }

    public function save(CacheItemInterface $item): bool
    {
        $itemData = $this->assertItemData($item->get());
        $cache = $this
            ->cache($item->getKey())
            ->set($itemData);

        if ($item instanceof PsrCacheItem) {
            $expiration = $item->expiration();

            if ($expiration !== null) {
                $ttl = $expiration->getTimestamp() - time();

                if ($ttl <= 0) {
                    return $this->deleteItem($item->getKey());
                }

                $cache->ttl($ttl);
            }
        }

        return $cache->put();
    }

    public function hasItem(string $key): bool
    {
        return $this->cache($key)->has();
    }

    public function deleteItem(string $key): bool
    {
        return $this
            ->cache($key)
            ->delete();
    }

    /**
     * @param string[] $keys
     * @return bool
     */
    public function deleteItems(array $keys): bool
    {
        foreach ($keys as $key) {
            if (!$this->deleteItem($key)) {
                return false;
            }
        }

        return true;
    }

    public function clear(): bool
    {
        $this->activate();

        NCache::clear(type: $this->type);

        return true;
    }

    public function saveDeferred(CacheItemInterface $item): bool
    {
        $this->deferred[$item->getKey()] = $item;

        return true;
    }

    public function commit(): bool
    {
        foreach ($this->deferred as $key => $item) {
            if (!$this->save($item)) {
                return false;
            }

            unset($this->deferred[$key]);
        }

        return true;
    }

    /**
     * @param mixed $values
     * @throws InvalidCacheArgumentException
     * @return ItemData
     */
    private function assertItemData(mixed $values): mixed
    {
        if (
            !\is_array($values) &&
            !\is_string($values) &&
            !\is_int($values) &&
            !\is_float($values) &&
            !\is_bool($values) &&
            !\is_callable($values) &&
            $values !== null
        ) {
            throw new InvalidCacheArgumentException(
                'Unsupported value type ' . \gettype($values),
            );
        }

        return $values;
    }

    private function validateKey(string $key): void
    {
        if ($key === '') {
            throw new InvalidCacheArgumentException(
                'Cache key cannot be empty.',
            );
        }

        if (preg_match('/[{}()\/\\\\@:]/', $key) === 1) {
            throw new InvalidCacheArgumentException(
                "Invalid PSR-6 cache key: {$key}",
            );
        }
    }
}
