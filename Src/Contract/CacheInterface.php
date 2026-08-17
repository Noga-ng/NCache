<?php

declare(strict_types=1);

namespace NCache\Contract;

use NCache\Config\CacheConfig;
use NCache\Enum\CType;
use NCache\Enum\TtlState;

/**
 * @phpstan-type ItemData array<array-key,mixed>|string|int|bool|float
 */
interface CacheInterface
{
    /**
     * @param non-empty-string $key
     * @param ?CType $type
     * @return self
     */
    public static function key(string $key, ?CType $type = null): self;

    /**
     * @param string $dir
     * @return static
     */
    public function dir(string $dir = ''): static;

    public function has(): bool;

    /**
     * @param ItemData|callable $data
     * @return bool
     */
    public function hasValidSignature(mixed $data): bool;

    /**
     * @param ItemData|callable $signature
     * @return static
     */
    public function signature(mixed $signature): static;

    /**
     * @param positive-int|null $ttl
     * @return static
     */
    public function ttl(?int $ttl = null): static;

    /**
     * @return int|null
     */
    public function ttlRemaining(): ?int;

    /**
     * @return TtlState|null
     */
    public function ttlState(): ?TtlState;

    /**
     * @param list<string>|string|null $tags
     * @return static
     */
    public function tags(mixed $tags): static;

    public static function invalidateTag(string $tag): bool;

    public static function getProfileActive(): string;

    /**
     * @return array{
     * state:bool,
     * entries:list<string>
     * }|null
     */
    public function getTags(): ?array;

    /**
     * @param ItemData|callable $data
     * @return static
     */
    public function set(mixed $data): static;

    /**
     * @param ItemData|callable $data
     * @return static
     */
    public function append(mixed $data): static;

    public function put(): bool;

    /**
     * @return array<mixed>|string|int|null
     */
    public function get(): mixed;

    /**
     * @return array{
     *     type: string,
     *     name: string,
     *     key: string,
     *     namespace:string|null,
     *     file: string|null,
     *     size: int|null,
     *     signature: string|null,
     *     ttl: int|null,
     *     expiresAt: int|null,
     *     tags: array{
     *              state:bool,
     *              entries:list<string>
     *      }|null
     * }|null
     */
    public function getRegistry(): ?array;

    /**
     * @return array<string,mixed>
     */
    public function show(): array;

    public function delete(): bool;

    /**
     * @param ?CType $type
     * @param string $dir
     * @return int
     */
    public static function clear(?CType $type = null, string $dir = ''): int;

    /**
     * load a file configuration
     * @param string $filename
     * @return CacheConfig
     */
    public static function config(?string $filename = null): CacheConfig;
}
