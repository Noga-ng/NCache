<?php

declare(strict_types=1);

namespace NCache\Driver;

use NCache\Config\Connection\MCached;
use NCache\Exceptions\InvalidCacheArgumentException;
use Memcached;

final class MemCache extends CacheDriver
{
    private ?MCached $connection = null;

    private function conn(): MCached
    {
        return $this->connection ??=
            new MCached();
    }

    private function client(): Memcached
    {
        return $this->conn()->connect();
    }

    private function namespace(): string
    {
        return $this->item->getDir() ?? 'default';
    }

    private function prefix(): string
    {
        return 'ncache:' . $this->namespace();
    }

    private function key(): string
    {
        return $this->prefix()
            . ':'
            . $this->item->hashedKey();
    }

    private function namespaceIndexKey(): string
    {
        return 'ncache:index:' . $this->namespace();
    }

    private function namespacesIndexKey(): string
    {
        return 'ncache:index:namespaces';
    }

    protected function format(): string
    {
        return serialize(
            $this->item->getData()
        );
    }

    public function save(): bool
    {
        $client = $this->client();

        if (!$client->set(
            $this->key(),
            $this->format(),
            0
        )) {
            return false;
        }

        $this->registerKey();
        $this->registerNamespace();

        return true;
    }

    public function exists(): bool
    {
        $client = $this->client();

        $client->get(
            $this->key()
        );

        return $client->getResultCode()
            === Memcached::RES_SUCCESS;
    }

    /**
     * @return array<array-key, mixed>|null
     */
    public function get(): ?array
    {
        $client = $this->client();

        $raw = $client->get(
            $this->key()
        );

        if (
            $raw === false
            && $client->getResultCode()
                === Memcached::RES_NOTFOUND
        ) {
            return null;
        }

        if (!\is_string($raw)) {
            throw new InvalidCacheArgumentException(
                'Memcached cache data must be a serialized string.'
            );
        }

        $data = unserialize(
            $raw,
            ['allowed_classes' => false]
        );

        if (!\is_array($data)) {
            throw new InvalidCacheArgumentException(
                'Invalid Memcached cache data.'
            );
        }

        return $data;
    }

    public function delete(): bool
    {
        $client = $this->client();

        $client->delete(
            $this->key()
        );

        $this->unregisterKey();

        return true;
    }

    public function clear(): int
    {
        $client = $this->client();

        $keys = $client->get(
            $this->namespaceIndexKey()
        );

        if (!\is_array($keys) || $keys === []) {
            return 0;
        }

        $deleted = $client->deleteMulti(
            $keys
        );

        $count = 0;

        foreach ($deleted as $status) {
            if ($status === true) {
                $count++;
            }
        }

        $client->delete(
            $this->namespaceIndexKey()
        );

        return $count;
    }

    public function clearAll(): int
    {
        $client = $this->client();

        $namespaces = $client->get(
            $this->namespacesIndexKey()
        );

        if (!\is_array($namespaces)) {
            return 0;
        }

        $count = 0;

        foreach ($namespaces as $namespace) {
            if (!\is_string($namespace)) {
                continue;
            }

            $indexKey = "ncache:index:{$namespace}";

            $keys = $client->get(
                $indexKey
            );

            if (!\is_array($keys)) {
                continue;
            }

            if ($keys !== []) {
                $deleted = $client->deleteMulti($keys);

                foreach ($deleted as $status) {
                    if ($status === true) {
                        $count++;
                    }
                }
            }

            $client->delete($indexKey);
        }

        $client->delete(
            $this->namespacesIndexKey()
        );

        return $count;
    }

    private function registerKey(): void
    {
        $client = $this->client();

        $index = $this->namespaceIndexKey();

        $keys = $client->get($index);

        if (!\is_array($keys)) {
            $keys = [];
        }

        $key = $this->key();

        if (!\in_array($key, $keys, true)) {
            $keys[] = $key;
        }

        if (!$client->set(
            $index,
            $keys,
            0
        )) {
            throw new InvalidCacheArgumentException(
                'Unable to update Memcached namespace index.'
            );
        }
    }

    private function unregisterKey(): void
    {
        $client = $this->client();

        $keys = $client->get(
            $this->namespaceIndexKey()
        );

        if (!\is_array($keys)) {
            return;
        }

        $keys = array_values(
            array_filter(
                $keys,
                fn(mixed $key): bool
                    => \is_string($key)
                    && $key !== $this->key()
            )
        );

        if ($keys === []) {
            $client->delete(
                $this->namespaceIndexKey()
            );

            return;
        }

        $client->set(
            $this->namespaceIndexKey(),
            $keys,
            0
        );
    }

    private function registerNamespace(): void
    {
        $client = $this->client();

        $namespaces = $client->get(
            $this->namespacesIndexKey()
        );

        if (!\is_array($namespaces)) {
            $namespaces = [];
        }

        $namespace = $this->namespace();

        if (!\in_array(
            $namespace,
            $namespaces,
            true
        )) {
            $namespaces[] = $namespace;
        }

        $client->set(
            $this->namespacesIndexKey(),
            $namespaces,
            0
        );
    }

    public function getFile(): ?string
    {
        return null;
    }
}
