<?php
declare (strict_types = 1);

namespace NCache;

use NCache\Config\CacheConfig;
use NCache\Contract\CacheInterface;
use NCache\Core\CacheItem\CacheItem;
use NCache\Core\CachePath;
use NCache\Core\Hash;
use NCache\Driver\CacheDriver;
use NCache\Enum\CType;
use NCache\Registry\DriverRegistry;

/**
 * @phpstan-type ItemData array<array-key,mixed>|string|int|bool|float
 */
final class NCache implements CacheInterface
{
    private CacheItem $cacheItem;

    /**
     * @param non-empty-string $key
     * @param CType $type
     */
    private function __construct(string $key, CType $type)
    {
        $basePath        = new CachePath(CacheConfig::config()->getBasePath());
        $this->cacheItem = new CacheItem($key, $type, $basePath);
    }

    /**
     * @param non-empty-string $key
     * @param CType $type
     * @return static
     */
    public static function key(string $key, CType $type): static
    {
        $instance = new NCache($key, $type);
        return $instance;
    }

    /**
     * @param non-empty-string $dir
     * @return static
     */
    public function dir(string $dir): static
    {
        $this->cacheItem->setDir($dir);
        return $this;
    }

    /**
     * Définit la valeur représentant l'état de la ressource.
     * Cette valeur est transformée en une signature interne
     * afin de détecter les changements.
     * @param ItemData $signature
     * @return static
     */
    public function signature(mixed $signature): static
    {
        $this->cacheItem->setSignature($signature);
        return $this;
    }

    /**
     * @param non-negative-int $ttl
     * @return static
     */
    public function ttl(int $ttl): static
    {
        $this->cacheItem->setTtl($ttl);
        return $this;
    }

    /**
     * @param ItemData $data
     * @return static
     */
    public function set(mixed $data): static
    {
        $this->cacheItem->setData($data);
        return $this;
    }

    /**
     * @param ItemData $data
     * @return static
     */
    public function append(mixed $data): static
    {
        $this->cacheItem->appendData($data);
        return $this;
    }

    public function has(): bool
    {
        return $this->driver()->exists();
    }

    public function store(): bool
    {
        return $this->driver()->save();
    }

    /**
     * @return array<mixed>|int|string|null
     */
    public function get(): mixed
    {
        $data = $this->driver()->get();
        return $data ?? null;
    }

    /**
     * @return array<string,mixed>
     */
    public function show(): array
    {
        return $this->cacheItem->toArray();
    }

    public function delete(): bool
    {
        return $this->driver()->delete();
    }

    /**
     * @param CType $type
     * @param string $dir
     * @return int
     */
    public static function clear(CType $type, string $dir = ""): int
    {
        $instance = new self("__key__", $type);
        $instance->cacheItem->setDir($dir);
        return $instance->driver()->clear();
    }

    /**
     * @param ItemData $data
     * @return bool
     */
    public function hasValidSignature(mixed $data): bool
    {
        $signature = (new Hash($data))->get();
        $cache     = $this->driver()->metaData();

        return (isset($cache['signature']) &&
            $cache['signature'] === $signature);
    }

    /**
     * @return CacheDriver
     */
    private function driver(): CacheDriver
    {
        return DriverRegistry::make(
            $this->cacheItem
        );
    }

    /**
     * @param string $baseDir
     * @return CacheConfig
     */
    public static function config(string $baseDir): CacheConfig
    {
        return CacheConfig::config($baseDir);
    }

}
