<?php

declare(strict_types=1);

namespace NCache\Contract;

use NCache\Enum\CType;

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

    public function has(): bool;

    /**
     * @param ItemData $data
     * @return bool
     */
    public function hasValidSignature(mixed $data): bool;

    /**
     * @param ItemData $signature
     * @return static
     */
    public function signature(mixed $signature): static;

    /**
     * @param positive-int|null $ttl
     * @return static
     */
    public function ttl(?int $ttl = null): static;

    /**
     * @param ItemData $data
     * @return static
     */
    public function set(mixed $data): static;

    /**
     * @param ItemData $data
     * @return static
     */
    public function append(mixed $data): static;

    public function put(): bool;

    /**
     * @return string|int|array<mixed>|null
     */
    public function get(): mixed;

    public function delete(): bool;

    /**
     * @param ?CType $type
     * @param string $dir
     * @return int
     */
    public static function clear(?CType $type = null, string $dir = ''): int;

}
