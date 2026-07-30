<?php

declare(strict_types=1);

namespace NCache\Core\CacheItem;

use NCache\Config\Ttl\Expiration;
use NCache\Core\CachePath;
use NCache\Core\Hash;
use NCache\Enum\CType;

final class CacheItem
{
    private ?Expiration $expiration = null;

    private ?string $signature = null;

    /**
     * @var array<mixed>
     */
    private array $data = [];

    public function __construct(
        private readonly string $key,
        private readonly CType $type,
        private CachePath $cachePath
    ) {
    }

    public function setDir(string $dir): void
    {
        $this->cachePath = $this->cachePath->dir($dir);
    }

    /**
     * @param array<mixed>|float|int|string $signature
     * @return void
     */
    public function setSignature(mixed $signature): void
    {
        $this->signature = (new Hash($signature,'sha256'))->get();
    }

    /**
     * @param array<mixed>|string|int|bool $data
     */
    public function setData(mixed $data): void
    {
        $this->data[] = $data;
    }

    public function setTtl(?int $ttl): void
    {
        $this->expiration = Expiration::fromTTL($ttl);
    }

    public function key(): string
    {
        return $this->key;
    }

    public function hashedKey(): string
    {
        return (new Hash($this->key))->get();
    }

    public function type(): CType
    {
        return $this->type;
    }

    public function typeName(): string
    {
        return $this->type->name;
    }

    public function path(): string
    {
        return $this->cachePath->getPath();
    }

    public function ttlValue(): ?int
    {
        return $this->expiration?->ttl();
    }

    public function expiredAt(): ?int
    {
        return $this->expiration?->timestamp();
    }

    public function file(): ?string
    {
        return match ($this->type) {
            CType::REDIS => null,
            CType::SQLite => $this->path(),
            default => rtrim($this->path(), "/\\")
                . DIRECTORY_SEPARATOR
                . $this->hashedKey(),
        };
    }

    public function getSignature():?string{
        return $this->signature;
    }

    /**
     * @return array<mixed>
     */
    public function getData():array{
        return $this->data;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'type' => $this->typeName(),
            'name' => $this->key(),
            'key' => $this->hashedKey(),
            'signature' => $this->signature,
            'ttl' => $this->ttlValue(),
            'expiresAt' => $this->expiredAt(),
            'data' => $this->data,
        ];
    }
}