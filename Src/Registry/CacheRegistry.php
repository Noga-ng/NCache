<?php

declare(strict_types=1);

namespace NCache\Registry;

use NCache\Core\CacheItem\CacheItem;
use NCache\Core\Files\CacheCleaner;
use NCache\Core\Files\ReadFile;
use NCache\Core\Files\WriteFile;
use NCache\Enum\CType;
use NCache\Exceptions\InvalidCacheArgumentException;

/**
 * @phpstan-type CrEntry array{
 *     type: string,
 *     name: string,
 *     key: string,
 *     file: string|null,
 *     signature: string|null,
 *     ttl: int|null,
 *     expiresAt: int|null
 * }
 *
 * @phpstan-type CrEntries array<string, CrEntry>
 *
 * @phpstan-type CrRegistry array{
 *     version: positive-int,
 *     entries: CrEntries
 * }
 */
final class CacheRegistry
{
    /**
     * @var positive-int
     */
    private const VERSION = 1;

    private ?string $file = null;

    public function __construct(
        private readonly CacheItem $item,
    ) {}

    /**
     * @return CrEntry
     */
    private function assemble(): array
    {
        return [
            'type' => $this->item->typeName(),
            'name' => $this->item->key(),
            'key' => $this->item->hashedKey(),
            'file' => $this->file,
            'signature' => $this->item->getSignature(),
            'ttl' => $this->item->ttlValue(),
            'expiresAt' => $this->item->expiredAt(),
        ];
    }

    /**
     * @return CrEntries
     */
    private function group(): array
    {
        return [
            $this->registryKey() => $this->assemble(),
        ];
    }

    /**
     * @return CrRegistry
     */
    private function emptyRegistry(): array
    {
        return [
            'version' => self::VERSION,
            'entries' => [],
        ];
    }

    /**
     * @return CrRegistry
     */
    private function metaData(): array
    {
        $registry = is_file($this->path())
            ? $this->readData()
            : $this->emptyRegistry();

        $registry['entries'] = array_replace(
            $registry['entries'],
            $this->group()
        );

        return $registry;
    }

    public function setFile(?string $file): void
    {
        $this->file = $file;
    }

    private function path(): string
    {
        return rtrim($this->item->basePath(), '/\\')
            . DIRECTORY_SEPARATOR
            . 'NCache.nc';
    }

    public function save(): bool
    {
        return $this->writeData(
            $this->metaData()
        );
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
            throw new InvalidCacheArgumentException(
                'Cache registry must contain an array.'
            );
        }

        if (
            !isset($data['version'])
            || !\is_int($data['version'])
        ) {
            throw new InvalidCacheArgumentException(
                'Registry version must be an integer.'
            );
        }

        if ($data['version'] !== self::VERSION) {
            throw new InvalidCacheArgumentException(
                "Unsupported registry version {$data['version']}."
            );
        }

        if (!\array_key_exists('entries', $data)
                || !\is_array($data['entries'])) {
            throw new InvalidCacheArgumentException(
                'Registry entries must be an array.'
            );
        }

        foreach ($data['entries'] as $key => $entry) {
            if (!\is_string($key) || !\is_array($entry)) {
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

        /** @var CrRegistry */
        return $data;
    }

    /**
     * @param array<mixed> $entry
     */
    private function validateEntry(array $entry): void
    {
        if (
            !isset($entry['type'])
            || !\is_string($entry['type'])
        ) {
            throw new \UnexpectedValueException(
                'Registry entry type must be a string.'
            );
        }

        if (
            !isset($entry['name'])
            || !\is_string($entry['name'])
        ) {
            throw new \UnexpectedValueException(
                'Registry entry name must be a string.'
            );
        }

        if (
            !isset($entry['key'])
            || !\is_string($entry['key'])
        ) {
            throw new \UnexpectedValueException(
                'Registry entry key must be a string.'
            );
        }

        foreach (['file', 'signature'] as $field) {
            if (
                !\array_key_exists($field, $entry)
                || (
                    $entry[$field] !== null
                    && !\is_string($entry[$field])
                )
            ) {
                throw new \UnexpectedValueException(
                    "{$field} must be a string or null."
                );
            }
        }

        foreach (['ttl', 'expiresAt'] as $field) {
            if (
                !\array_key_exists($field, $entry)
                || (
                    $entry[$field] !== null
                    && !\is_int($entry[$field])
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
    public function getRegistry(): array
    {
        $data = is_file($this->path())
            ? $this->readData()
            : $this->emptyRegistry();

        return $data;
    }

    /**
     * @return CrEntries
     */
    public function getAll(): array
    {
        return $this->getRegistry()['entries'];
    }

    /**
     * @return CrEntry|null
     */
    public function get(): ?array
    {
        $entries = $this->getAll();
        return $entries[$this->registryKey()] ?? null;
    }

    public function has(): bool
    {
        return \array_key_exists(
            $this->registryKey(),
            $this->getAll()
        );
    }

    public function remove(): bool
    {
        $data = $this->getRegistry();
        $entries = $data['entries'];
        $key = $this->registryKey();

        if (!\array_key_exists($key, $entries)) {
            return true;
        }

        unset($data['entries'][$key]);

        return $this->writeData($data);
    }

    public function removeMissing(): int
    {
        $data = $this->getRegistry();
        $entries = $data['entries'];
        $count = 0;

        $currentType = $this->item->typeName();
        $currentDirectory = $this->item->path();

        foreach ($entries as $key => $entry) {
            if ($entry['type'] !== $currentType) {
                continue;
            }

            $file = $entry['file'];

            if ($file === null) {
                continue;
            }

            if (dirname($file) !== $currentDirectory) {
                continue;
            }

            if (is_file($file)) {
                continue;
            }

            unset($data['entries'][$key]);
            $count++;
        }

        if ($count === 0) {
            return 0;
        }

        $this->writeData($data);

        return $count;
    }

    public function removeCurrentScope(): int
    {
        $data = $this->getRegistry();
        $count = 0;

        $currentType = $this->item->typeName();

        foreach ($data['entries'] as $key => $entry) {
            if ($entry['type'] !== $currentType) {
                continue;
            }

            unset($data['entries'][$key]);
            $count++;
        }

        if ($count === 0) {
            return 0;
        }

        $this->writeData($data);

        return $count;
    }


    public function clear(): int
    {
        if (!is_file($this->path())) {
            return 0;
        }

        $count = \count(
            $this->getAll()
        );

        (new CacheCleaner(['nc']))
            ->delete($this->path());

        return $count;
    }

    public function registryKey(): string
    {
        return $this->item->hashedKey();
    }

    /**
     * @param CrRegistry $registry
     */
    private function writeData(array $registry): bool
    {
        if ($registry['entries'] === []) {
            return (new CacheCleaner(['nc']))
                ->delete($this->path());
        }

        return (new WriteFile(
            $this->path(),
            serialize($registry)
        ))->save();
    }
}
