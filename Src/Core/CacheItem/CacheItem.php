<?php

declare(strict_types=1);

namespace NCache\Core\CacheItem;

use NCache\Config\ConfigItem;
use NCache\Contract\Clock;
use NCache\Core\CachePath;
use NCache\Core\Hash;
use NCache\Core\TtlManager\Expiration;
use NCache\Enum\CType;

/**
 * @phpstan-type ItemData array<array-key,mixed>|string|int|bool|float|null
 */
final class CacheItem
{
    /** @var Expiration|null */
    private ?Expiration $expiration = null;
    private bool $ttlDefined = false;
    /** @var string|null */
    private ?string $signature = null;
    /** @var array<array-key,mixed> */
    private array $data = [];
    private CachePath $cachePath;

    public function __construct(
        private readonly string $key,
        private readonly CType $type,
        private readonly ConfigItem $config,
    ) {
        $this->cachePath = new CachePath(
            $this->config->getBasePath(),
        );

        $namespace = $this->config->getNamespace();

        if ($namespace !== null && trim($namespace) !== '') {
            $this->cachePath = $this->cachePath->dir(
                $namespace,
            );
        }
    }

    /**
     * @param string $dirname
     * @return void
     */
    public function setDir(?string $dirname = null): void
    {
        $namespace = ($dirname === null || trim($dirname) === '')
            ? $this->config->getNamespace()
            : $dirname;

        $this->cachePath = $this->cachePath->dir($namespace);
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
    public function appendData(mixed $data): void
    {
        $data = \is_array($data) ? $data : [$data];
        $this->data = [...$this->data, ...$data];
    }

    /**
     * @param non-negative-int|null $ttl
     * @return void
     */
    public function setTtl(?int $ttl, Clock $clock): void
    {
        $ttl = ($ttl === null)
        ? $this->config->getDefaultTtl()
        : $ttl;

        $this->expiration = Expiration::fromTTL($ttl, $clock);
        $this->ttlDefined = true;
    }

    /**
     * @param int|null $ttl
     * @param int|null $expiration
     * @param Clock $clock
     * @return void
     */
    public function restoreExpiration(?int $ttl, ?int $expiration, Clock $clock): void
    {
        $this->expiration = Expiration::restore(
            $ttl,
            $expiration,
            $clock,
        );
    }

    public function ttlWasDefined(): bool
    {
        return $this->ttlDefined;
    }

    public function key(): string
    {
        return $this->key;
    }

    public function hashedKey(): string
    {
        $dir = $this->getDir() ?? 'default';
        return (new Hash([
            'type' => $this->typeName(),
            'dir' => $dir,
            'key' => $this->key(),
        ]))->get();
    }

    public function type(): CType
    {
        return $this->type;
    }

    public function typeName(): string
    {
        return $this->type->name;
    }

    public function getDir(): ?string
    {
        return $this->cachePath->dirname();
    }

    public function path(): string
    {
        return $this->cachePath->getPath();
    }

    public function basePath(): string
    {
        return $this->cachePath->getBasePath();
    }

    /**
     * @return int|null
     */
    public function ttlValue(): ?int
    {
        return $this->expiration?->ttl();
    }

    /**
     * @return int|null
     */
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
            CType::MEMCACHED => null,
            CType::SQLite => $this->path(),
            default => rtrim($this->path(), '/\\')
                . DIRECTORY_SEPARATOR
                . $this->hashedKey(),
        };
    }

    /**
     * @return string|null
     */
    public function getSignature(): ?string
    {
        return $this->signature;
    }

    /**
     * @return array{
     *     host:string,
     *     port:int,
     *     timeout:int|float,
     *     password:string|null,
     *     database:int
     * }|null
     */
    public function redisConfig(): ?array
    {
        if ($this->type !== CType::REDIS) {
            return null;
        }

        return $this->config->getRedis();
    }

    /**
     * @return array{
     *     host:string,
     *     port:int,
     *     weight:int
     * }|null
     */
    public function memcachedConfig(): ?array
    {
        if ($this->type !== CType::MEMCACHED) {
            return null;
        }

        return $this->config->getMemcached();
    }

    public function extension(): ?string
    {
        return match ($this->type) {
            CType::JSON => 'json',
            CType::SERIALIZE,
            CType::ARRAY_PHP,
            CType::STRING
                => $this->config->getExtension(
                    $this->type,
                ),
            default => null,
        };
    }

    /**
     * @return array<array-key,mixed>
     */
    public function getData(): array
    {
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
            'namespace' => $this->getDir(),
            'signature' => $this->getSignature(),
            'ttl' => $this->ttlValue(),
            'expiresAt' => $this->expiredAt(),
            'data' => $this->data,
        ];
    }
}
