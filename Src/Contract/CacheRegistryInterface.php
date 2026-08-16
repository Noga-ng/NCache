<?php

declare(strict_types=1);

namespace NCache\Contract;

/**
 * @phpstan-type CrEntry array{
 *     type: string,
 *     name: string,
 *     key: string,
 *     namespace:string|null,
 *     file: string|null,
 *     size: int|null,
 *     signature: string|null,
 *     ttl: int|null,
 *     expiresAt: int|null
 * }
 */
interface CacheRegistryInterface
{
    public function save(): bool;

    /**
     * @return CrEntry|null
     */
    public function get(): ?array;

    public function has(): bool;

    public function remove(): bool;

    public function clear(): int;
}
