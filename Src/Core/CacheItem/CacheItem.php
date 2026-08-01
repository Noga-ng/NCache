<?php

declare(strict_types=1);

namespace NCache\Core\CacheItem;

use NCache\Config\Ttl\Expiration;
use NCache\Core\CachePath;
use NCache\Core\Hash;
use NCache\Enum\CType;

/**
 * @phpstan-type ItemData array<array-key,mixed>|string|int|bool|float
 */
final class CacheItem
{
    /**
     * @var Expiration|null
     */
    private ?Expiration $expiration = null;

    /**
     * @var string|null
     */
    private ?string $signature = null;

    /**
     * @var array<array-key,mixed>
     */
    private array $data = [];

    public function __construct(
        private readonly string $key,
        private readonly CType $type,
        private CachePath $cachePath
    ) {}

    public function setDir(string $dir): void
    {
        $this->cachePath = $this->cachePath->dir($dir);
    }

    /**
     * @param ItemData $signature
     * @return void
     */
    public function setSignature(mixed $signature): void
    {
        $this->signature = (new Hash($signature))->get();
    }

    /**
     * @param ItemData $data
     */
    public function setData(mixed $data): void
    {
        $this->data = \is_array($data) ? $data : [$data];
    }

    /**
     * @param ItemData $data
     */
    public function appendData(mixed $data):void{
        $data = \is_array($data) ? $data : [$data];
        $this->data = [...$this->data,...$data];
    }

    /**
     * @param non-negative-int|null $ttl
     * @return void
     */
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

    public function basePath():string{
        return $this->cachePath->geBasePath();
    }

    public function ttlValue(): ?int
    {
        return $this->expiration?->ttl();
    }

    public function expiredAt(): ?int
    {
        return $this->expiration?->timestamp();
    }

    /**
     * @return string|null
     */
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

    /**
     * @return string|null
     */
    public function getSignature():?string{
        return $this->signature;
    }

    /**
     * @return array<array-key,mixed>
     */
    public function getData():array{
        return $this->data;
    }

    /**
     * @return array{
     * data: array<array-key,mixed>, 
     * expiresAt: int|null, 
     * key: string, 
     * name: string, 
     * signature: string|null, 
     * ttl: int|null, 
     * type: string
     * }
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