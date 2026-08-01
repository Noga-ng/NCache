<?php declare(strict_types=1);

namespace NCache\Registry;

use NCache\Core\CacheItem\CacheItem;
use NCache\Core\Files\CacheCleaner;
use NCache\Core\Files\ReadFile;
use NCache\Core\Files\WriteFile;
use NCache\Enum\CType;

/**
 * @phpstan-type CrEntry array{
 *     type: string,
 *     key: string,
 *     file: string|null,
 *     signature: string|null,
 *     ttl: int|null,
 *     expiresAt: int|null
 * }
 *
 * @phpstan-type CrRegistry array<string, CrEntry>
 */
final class CacheRegistry
{
    public function __construct(
        private readonly CacheItem $item
    ) {}

    /**
     * @return CrEntry
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
     * @return CrRegistry
     */
    private function group(): array
    {
        return [
            $this->item->hashedKey() => $this->assemble(),
        ];
    }

    /**
     * @return CrRegistry
     */
    private function metaData(): array
    {
        $data = is_file($this->path())
            ? $this->readData()
            : [];

        return array_replace($data, $this->group());
    }

    private function path(): string
    {
        return $this->item->basePath()
            . DIRECTORY_SEPARATOR
            . 'NCache.nc';
    }

    public function save(): bool
    {
        return (new WriteFile(
            $this->path(),
            serialize($this->metaData())
        ))->save();
    }

    /**
     * @return CrRegistry
     */
    private function readData(): array
    {
        $data = (new ReadFile(
            $this->path(),
            CType::SERIALIZE
        ))->get();

        if (!\is_array($data)) {
            throw new \UnexpectedValueException(
                'Cache registry must contain an array.'
            );
        }

        foreach ($data as $key => $entry) {
            if (!\is_string($key) || !is_array($entry)) {
                throw new \UnexpectedValueException(
                    'Invalid cache registry entry.'
                );
            }

            $this->validateEntry($entry);

            if ($entry['key'] !== $key) {
                throw new \UnexpectedValueException(
                    'Registry entry key does not match its index.'
                );
            }
        }

        /** @var CrRegistry $data */
        return $data;
    }

    /**
     * @param array<mixed> $entry
     */
    private function validateEntry(array $entry): void
    {
        if (
            !isset($entry['type']) ||
            !\is_string($entry['type'])
        ) {
            throw new \UnexpectedValueException(
                'Registry entry type must be a string.'
            );
        }

        if (
            !isset($entry['key']) ||
            !\is_string($entry['key'])
        ) {
            throw new \UnexpectedValueException(
                'Registry entry key must be a string.'
            );
        }

        foreach (['file', 'signature'] as $field) {
            if (
                !\array_key_exists($field, $entry) ||
                (
                    $entry[$field] !== null &&
                    !\is_string($entry[$field])
                )
            ) {
                throw new \UnexpectedValueException(
                    "{$field} must be a string or null."
                );
            }
        }

        foreach (['ttl', 'expiresAt'] as $field) {
            if (
                !\array_key_exists($field, $entry) ||
                (
                    $entry[$field] !== null &&
                    !\is_int($entry[$field])
                )
            ) {
                throw new \UnexpectedValueException(
                    "{$field} must be an integer or null."
                );
            }
        }
    }

    /**
     * @return CrRegistry
     */
    public function getValues(): array
    {
        return is_file($this->path())
            ? $this->readData()
            : [];
    }

    /**
     * @return CrEntry|null
     */
    public function get(): ?array
    {
        $data = $this->getValues();
        return $data[$this->item->hashedKey()] ?? null;
    }

    public function has(): bool
    {
        return \array_key_exists(
            $this->item->hashedKey(),
            $this->getValues()
        );
    }

    public function remove(): bool
    {
        $data = $this->getValues();
        $key = $this->item->hashedKey();

        if (!array_key_exists($key, $data)) {
            return true;
        }

        unset($data[$key]);

        if ($data === []) {
            return (new CacheCleaner(['nc']))
                ->delete($this->path());
        }

        return (new WriteFile(
            $this->path(),
            serialize($data)
        ))->save();
    }

    public function clear(): int
    {
        $count = count(
            $this->getValues()
        );

        (new CacheCleaner(['nc']))
            ->delete($this->path());

        return $count;
    }
}
