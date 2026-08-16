<?php

declare(strict_types=1);

namespace NCache\Registry;

use NCache\Core\CacheItem\CacheItem;
use NCache\Driver\ArrayCache;
use NCache\Driver\CacheDriver;
use NCache\Driver\JsonCache;
use NCache\Driver\MemCache;
use NCache\Driver\RedisCache;
use NCache\Driver\SerializeCache;
use NCache\Driver\SqliteCache;
use NCache\Driver\StringCache;
use NCache\Enum\CType;
use NCache\Exceptions\InvalidCacheArgumentException;

final class DriverRegistry
{
    /**
     * @var array<string, class-string<CacheDriver>>
     */
    private static array $drivers = [
        'SERIALIZE' => SerializeCache::class,
        'JSON' => JsonCache::class,
        'ARRAY_PHP' => ArrayCache::class,
        'STRING' => StringCache::class,
        'REDIS' => RedisCache::class,
        'SQLite' => SqliteCache::class,
        'MEMCACHED' => MemCache::class,
    ];

    public static function register(CType $type, string $driver): void
    {
        if (!is_subclass_of($driver, CacheDriver::class)) {
            throw new InvalidCacheArgumentException(
                "{$driver} must extend CacheDriver",
            );
        }

        self::$drivers[$type->name] = $driver;
    }

    public static function make(CacheItem $item): CacheDriver
    {
        $name = $item->typeName();
        $drivers = self::$drivers[$name] ?? null;
        if ($drivers === null) {
            throw new InvalidCacheArgumentException(
                "No driver registered for {$name}",
            );
        }

        return new $drivers($item);
    }
}
