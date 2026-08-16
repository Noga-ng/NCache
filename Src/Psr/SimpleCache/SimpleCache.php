<?php

declare(strict_types=1);

namespace NCache\Psr\SimpleCache;

use DateInterval;
use NCache\Enum\CType;
use NCache\NCache;
use NCache\Psr\SimpleCache\Exceptions\InvalidCacheArgumentException;
use Psr\SimpleCache\CacheInterface;

/**
 * @phpstan-type ItemData array<array-key,mixed>|string|int|bool|float|null
 */
final class SimpleCache implements CacheInterface
{
    public function __construct(
        private readonly ?string $config = null,
        private readonly string $profile = 'default',
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

    private function cache(
        string $key,
    ): NCache {
        $this->validateKey(
            $key,
        );

        $this->activate();

        return NCache::key(
            $key,
            $this->type,
        );
    }

    /**
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $cache = $this->cache($key);

        if (!$cache->has()) {
            return $default;
        }

        return $cache->get();
    }

    /**
     * @param string $key
     * @param mixed $value
     * @param null|int|DateInterval $ttl
     * @return bool
     */
    public function set(string $key, mixed $value, null|int|DateInterval $ttl = null): bool
    {
        $values = $this->assertItemData($value);
        $cache = $this
            ->cache($key)
            ->set($values);

        if ($ttl !== null) {
            $seconds = $this->ttlToSeconds($ttl);

            if ($seconds <= 0) {
                return $this->delete($key);
            }

            $cache->ttl($seconds);
        }

        return $cache->put();
    }

    public function delete(string $key): bool
    {
        return $this
            ->cache($key)
            ->delete();
    }

    public function clear(): bool
    {
        $this->activate();
        NCache::clear();
        return true;
    }

    public function has(string $key): bool
    {
        return $this
            ->cache($key)
            ->has();
    }

    /**
     * @param iterable<string> $keys
     * @param mixed $default
     * @return iterable<string, mixed>
     */
    public function getMultiple(iterable $keys, mixed $default = null): iterable
    {
        $result = [];

        foreach ($keys as $key) {
            $result[$key] = $this->get($key, $default);
        }

        return $result;
    }

    /**
     * @param iterable<mixed, mixed> $values
     */
    public function setMultiple(iterable $values, null|int|DateInterval $ttl = null): bool
    {
        foreach ($values as $key => $value) {
            if (!\is_string($key)) {
                throw new InvalidCacheArgumentException(
                    'Cache key must be a string.',
                );
            }

            if (!$this->set(
                $key,
                $value,
                $ttl,
            )) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param iterable<string> $keys
     * @return bool
     */
    public function deleteMultiple(iterable $keys): bool
    {
        foreach ($keys as $key) {
            if (!$this->delete($key)) {
                return false;
            }
        }

        return true;
    }

    private function ttlToSeconds(int|DateInterval $ttl): int
    {
        if (\is_int($ttl)) {
            return $ttl;
        }

        $now = new \DateTimeImmutable();

        return $now
            ->add($ttl)
            ->getTimestamp()
            - $now->getTimestamp();
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
                "Invalid PSR-16 cache key: {$key}",
            );
        }
    }

    /**
     * @return ItemData
     */
    private function assertItemData(mixed $values): mixed
    {
        if (!\is_array($values) &&
                !\is_string($values) &&
                !\is_int($values) &&
                !\is_bool($values) &&
                !\is_float($values) &&
                $values !== null) {
            throw new InvalidCacheArgumentException(
                'Unsupported cache value type.',
            );
        }
        return $values;
    }
}
