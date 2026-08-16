<?php

declare(strict_types=1);

namespace NCache\Registry;

use NCache\Contract\CacheRegistryInterface;
use NCache\Core\CacheItem\CacheItem;
use NCache\Core\Files\CacheCleaner;
use NCache\Core\Files\ReadFile;
use NCache\Core\Files\WriteFile;
use NCache\Enum\CType;
use NCache\Exceptions\CacheRegistryException;
use NCache\Exceptions\InvalidCacheArgumentException;

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
 *
 * @phpstan-type CrEntries array<string, CrEntry>
 *
 * @phpstan-type CrRegistry array{
 *     version: positive-int,
 *     entries: CrEntries
 * }
 */
final class CacheRegistry implements CacheRegistryInterface
{
    /**
     * @var positive-int
     */
    private const VERSION = 1;

    private ?string $file = null;

    public function __construct(
        private readonly CacheItem $item,
    ) {
    }

    /**
     * @return CrEntry
     */
    private function assemble(): array
    {
        return [
            'type' => $this->item->typeName(),
            'name' => $this->item->key(),
            'key' => $this->item->hashedKey(),
            'namespace' => $this->item->getDir(),
            'file' => $this->file,
            'size' => $this->fileSize(),
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

    public function setFile(?string $file): void
    {
        $this->file = $file;
    }

    private function fileSize(): ?int
    {
        $size = $this->file !== null
            ? filesize($this->file)
            : false;

        return $size !== false ? $size : null;
    }

    private function path(): string
    {
        return rtrim(
            $this->item->basePath(),
            '/\\',
        )
            . DIRECTORY_SEPARATOR
            . 'NCache.nc';
    }

    public function save(): bool
    {
        return $this->transaction(
            function (array $data): array {
                $entries = $data['entries'];
                $data['entries'] = array_replace(
                    $entries,
                    $this->group(),
                );

                return [
                    'data' => $data,
                    'result' => true,
                ];
            },
        );
    }

    /**
     * @return CrRegistry
     */
    private function readData(): array
    {
        $data = (new ReadFile(
            $this->path(),
            CType::SERIALIZE,
        ))->get();

        if (!\is_array($data)) {
            throw new InvalidCacheArgumentException(
                'Cache registry must contain an array.',
            );
        }

        if (
            !isset($data['version']) ||
            !\is_int($data['version'])
        ) {
            throw new InvalidCacheArgumentException(
                'Registry version must be an integer.',
            );
        }

        if ($data['version'] !== self::VERSION) {
            throw new InvalidCacheArgumentException(
                "Unsupported registry version {$data['version']}.",
            );
        }

        if (!\array_key_exists('entries', $data) ||
                !\is_array($data['entries'])) {
            throw new InvalidCacheArgumentException(
                'Registry entries must be an array.',
            );
        }

        foreach ($data['entries'] as $key => $entry) {
            if (!\is_string($key) || !\is_array($entry)) {
                throw new InvalidCacheArgumentException(
                    'Invalid cache registry entry.',
                );
            }

            $this->validateEntry($entry);

            if ($entry['key'] !== $key) {
                throw new InvalidCacheArgumentException(
                    'Registry entry key does not match its index.',
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
            !isset($entry['type']) ||
            !\is_string($entry['type'])
        ) {
            throw new InvalidCacheArgumentException(
                'Registry entry type must be a string.',
            );
        }

        if (
            !isset($entry['name']) ||
            !\is_string($entry['name'])
        ) {
            throw new InvalidCacheArgumentException(
                'Registry entry name must be a string.',
            );
        }

        if (
            !isset($entry['key']) ||
            !\is_string($entry['key'])
        ) {
            throw new InvalidCacheArgumentException(
                'Registry entry key must be a string.',
            );
        }

        if (!\array_key_exists('namespace', $entry) ||
                (
                    $entry['namespace'] !== null &&
                    !\is_string($entry['namespace'])
                )) {
            throw new InvalidCacheArgumentException(
                'Registry entry namespace must be a string or null.',
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
                throw new InvalidCacheArgumentException(
                    "{$field} must be a string or null.",
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
                throw new InvalidCacheArgumentException(
                    "{$field} must be an integer or null.",
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
            $this->getAll(),
        );
    }

    public function remove(): bool
    {
        return $this->transaction(
            function (array $data): array {
                $key = $this->item->hashedKey();

                if (!isset($data['entries'][$key])) {
                    return [
                        'data' => $data,
                        'result' => true,
                    ];
                }

                unset($data['entries'][$key]);

                return [
                    'data' => $data,
                    'result' => true,
                ];
            },
        );
    }

    public function removeMissing(): int
    {
        return $this->transaction(
            function (array $data): array {
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
                    return [
                        'data' => $data,
                        'result' => 0,
                    ];
                }

                return [
                    'data' => $data,
                    'result' => $count,
                ];
            },
        );
    }

    public function removeCurrentScope(): int
    {
        return $this->transaction(
            function (array $data): array {
                $count = 0;

                $currentType = $this->item->typeName();

                $currentNamespace = $this->item->getDir();

                foreach ($data['entries'] as $key => $entry) {
                    if ($entry['type'] !== $currentType) {
                        continue;
                    }

                    if ($entry['namespace'] !== $currentNamespace) {
                        continue;
                    }

                    unset($data['entries'][$key]);
                    $count++;
                }

                return [
                    'data' => $data,
                    'result' => $count,
                ];
            },
        );
    }

    public function removeByType(): int
    {
        return $this->transaction(
            function (array $data): array {
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
                    return [
                        'data' => $data,
                        'result' => 0,
                    ];
                }

                return [
                    'data' => $data,
                    'result' => $count,
                ];
            },
        );
    }

    public function clear(): int
    {
        return $this->transaction(
            function (array $data): array {
                $count = \count(
                    $data['entries'],
                );

                $data['entries'] = [];

                return [
                    'data' => $data,
                    'result' => $count,
                ];
            },
        );
    }

    public function registryKey(): string
    {
        return $this->item->hashedKey();
    }

    /**
     * @template TResult
     *
     * @param callable(CrRegistry): array{
     *     data: CrRegistry,
     *     result: TResult
     * } $callback
     *
     * @return TResult
     */
    private function transaction(callable $callback): mixed
    {
        $lockFile = $this->path() . '.lock';

        $handle = fopen(
            $lockFile,
            'c',
        );

        if ($handle === false) {
            throw new CacheRegistryException(
                "Unable to open registry lock: {$lockFile}",
            );
        }

        $locked = false;

        try {
            if (!flock($handle, LOCK_EX)) {
                throw new CacheRegistryException(
                    "Unable to lock registry: {$this->path()}",
                );
            }

            $locked = true;

            $data = is_file($this->path())
                ? $this->readData()
                : $this->emptyRegistry();

            $transaction = $callback($data);

            $newData = $transaction['data'];

            if ($newData !== $data) {
                $this->persistRegistry(
                    $newData,
                );
            }

            return $transaction['result'];
        } finally {
            if ($locked) {
                flock(
                    $handle,
                    LOCK_UN,
                );
            }

            fclose(
                $handle,
            );
        }
    }

    /**
     * @param CrRegistry $data
     */
    private function persistRegistry(array $data): void
    {
        if ($data['entries'] === []) {
            if (is_file($this->path()) &&
                !(new CacheCleaner(['nc']))
                    ->delete($this->path())) {
                throw new CacheRegistryException(
                    "Unable to remove empty registry: {$this->path()}",
                );
            }
            return;
        }

        if (!$this->writeData($data)) {
            throw new CacheRegistryException(
                "Unable to write registry: {$this->path()}",
            );
        }
    }

    /**
     * @param CrRegistry $registry
     */
    private function writeData(array $registry): bool
    {
        return (new WriteFile(
            $this->path(),
            serialize($registry),
        ))->save();
    }
}
