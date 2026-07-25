<?php
declare(strict_types=1);

namespace NCache\Registry;

use NCache\Driver\CacheDriver;
use NCache\Driver\PhpFileArrayCache;
use NCache\Driver\PhpFileJsonCache;
use NCache\Enum\CType;
use InvalidArgumentException;

final class DriverRegistry
{
    /**
     * @var array<string, class-string<CacheDriver>>
     */
    private static array $drivers = [
        CType::ARRAY->name =>PhpFileArrayCache::class,
        CType::JSON->name =>PhpFileJsonCache::class
    ];

    public static function register(CType $type,string $driver): void {

    if (!is_subclass_of($driver, CacheDriver::class)) {
        throw new InvalidArgumentException(
            "{$driver} must extend CacheDriver"
        );
    }

    self::$drivers[$type->name] = $driver;
    }

    public static function make(CType $type,string $file): CacheDriver {
       $name = $type->name;
       $drivers = self::$drivers[$name] ?? null;
        if ($drivers === null) {
            throw new InvalidArgumentException(
                "No driver registered for {$name}"
            );
        }

        return new $drivers($file);
    }

}