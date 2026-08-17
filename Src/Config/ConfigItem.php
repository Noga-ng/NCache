<?php

declare(strict_types=1);

namespace NCache\Config;

use NCache\Enum\CType;

/**
 * @phpstan-type RedisConfig array{
 *     host:string,
 *     port:int,
 *     timeout:int|float,
 *     password:string|null,
 *     database:int
 * }
 *
 * @phpstan-type MemcachedConfig array{
 *     host:string,
 *     port:int,
 *     weight:int
 * }
 *
 * @phpstan-type Drivers array{
 *     redis?:RedisConfig,
 *     memcached?:MemcachedConfig
 * }
 *
 * @phpstan-type Extensions array<string,string>
 *
 * @phpstan-type ResolvedProfile array{
 *     cachePath:string,
 *     defaultDriver:string|null,
 *     namespace:string|null,
 *     defaultTags: list<string>|null,
 *     extensions:Extensions,
 *     defaultTtl:int|null,
 *     drivers:Drivers
 * }
 */
final class ConfigItem
{
    /**
     * @param ResolvedProfile $entry
     */
    public function __construct(
        private readonly string $name,
        private readonly array $entry,
    ) {
    }

    public function profile(): string
    {
        return $this->name;
    }

    public function getBasePath(): string
    {
        return $this->entry['cachePath'];
    }

    public function getDefaultDriver(): ?CType
    {
        return match ($this->entry['defaultDriver']) {
            'SERIALIZE' => CType::SERIALIZE,
            'JSON' => CType::JSON,
            'STRING' => CType::STRING,
            'ARRAY_PHP' => CType::ARRAY_PHP,
            'REDIS' => CType::REDIS,
            'MEMCACHED' => CType::MEMCACHED,
            'SQLite' => CType::SQLite,
            default => null,
        };
    }

    public function getNamespace(): ?string
    {
        return $this->entry['namespace'];
    }

    public function getDefaultTtl(): ?int
    {
        return $this->entry['defaultTtl'];
    }

    /**
     * @return list<string>|null
     */
    public function getDefaultTags(): ?array
    {
        return $this->entry['defaultTags'];
    }

    /**
     * @return Extensions
     */
    public function getExtensions(): array
    {
        return $this->entry['extensions'];
    }

    public function getExtension(CType $type): ?string
    {
        return $this->entry['extensions'][$type->name]
            ?? null;
    }

    /**
     * @return Drivers
     */
    public function getDrivers(): array
    {
        return $this->entry['drivers'];
    }

    /**
     * @return RedisConfig|null
     */
    public function getRedis(): ?array
    {
        return $this->entry['drivers']['redis']
            ?? null;
    }

    /**
     * @return MemcachedConfig|null
     */
    public function getMemcached(): ?array
    {
        return $this->entry['drivers']['memcached']
            ?? null;
    }

    /**
     * @return ResolvedProfile
     */
    public function getData(): array
    {
        return $this->entry;
    }
}
