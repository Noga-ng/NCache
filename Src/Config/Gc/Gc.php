<?php

declare(strict_types=1);

namespace NCache\Config\Gc;

use NCache\Core\CacheItem\CacheItem;
use NCache\Core\Files\ReadFile;
use NCache\Core\Files\WriteFile;
use NCache\Enum\CType;

/**
 * @phpstan-type GcEntry array{
 *     type: string,
 *     key: string,
 *     file: string|null,
 *     signature: string|null,
 *     ttl: int|null,
 *     expiresAt: int|null
 * }
 *
 * @phpstan-type GcRegistry array<string, GcEntry>
 */
final class Gc
{
    public function __construct(
        private readonly CacheItem $item
    ) {}

    /**
     * @return GcEntry
     */
    private function assemble(): array
    {
        return [
            'type' => $this->item->typeName(),
            'key' => $this->item->hashedKey(),
            'file' => $this->item->file(),
            'signature' => $this->item->getSignature(),
            'ttl' => $this->item->ttlValue(),
            'expiresAt' => $this->item->expiredAt(),
        ];
    }

    /**
     * @return GcRegistry
     */
    public function group(): array
    {
        return [
            $this->item->hashedKey() => $this->assemble(),
        ];
    }

    /**
     * @return GcRegistry
     */
    private function metaData(): array
    {
        $data = is_file($this->path())
            ? $this->readData()
            : [];

        return array_replace($data, $this->group());
    }

    public function path(): string
    {
        return $this->item->basePath()
            . DIRECTORY_SEPARATOR
            . 'NCache.gc';
    }

    public function save(): bool
    {
        return (new WriteFile(
            $this->path(),
            serialize($this->metaData())
        ))->save();
    }

    /**
     * @return GcRegistry
     */
    private function readData(): array
    {
        /**
         * @var GcRegistry
         */
        $data = (new ReadFile(
            $this->path(),
            CType::SERIALIZE
        ))->get();

        return $data;
    }

    /**
     * @return GcRegistry
     */
    public function getValues(): array
    {
        return is_file($this->path())
            ? $this->readData()
            : [];
    }

    /**
     * @return GcEntry|null
     */
    public function get(): ?array
    {
        $data = $this->getValues();

        return $data[$this->item->hashedKey()] ?? null;
    }
}