<?php

declare(strict_types=1);

namespace NCache\Driver;

use NCache\Config\Connection\RedisConn;
use NCache\Exceptions\InvalidCacheArgumentException;

final class RedisCache extends CacheDriver
{
    private ?RedisConn $connection = null;

    private function conn(): RedisConn
    {
        return $this->connection ??=
            new RedisConn();
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

    protected function format(): string
    {
        return serialize(
            $this->item->getData()
        );
    }

    public function save(): bool
    {
        return $this
            ->conn()
            ->connect()
            ->set(
                $this->key(),
                $this->format()
            );
    }

    public function exists(): bool
    {
        return $this
            ->conn()
            ->connect()
            ->exists(
                $this->key()
            ) > 0;
    }

    /**
     * @return array<array-key, mixed>|null
     */
    public function get(): ?array
    {
        $raw = $this
            ->conn()
            ->connect()
            ->get(
                $this->key()
            );

        if ($raw === false) {
            return null;
        }

        if (!\is_string($raw)) {
            throw new InvalidCacheArgumentException(
                'Redis cache data must be a serialized string.'
            );
        }

        $data = unserialize(
            $raw,
            [
                'allowed_classes' => false,
            ]
        );

        if (!\is_array($data)) {
            throw new InvalidCacheArgumentException(
                'Invalid Redis cache data.'
            );
        }

        return $data;
    }

    public function delete(): bool
    {
        $this
            ->conn()
            ->connect()
            ->del(
                $this->key()
            );

        return true;
    }

    public function clear(): int
    {
        if ($this->item->getDir() === null) {
            return $this->clearAll();
        }

        return $this->deleteByPattern(
            $this->prefix() . ':*'
        );
    }

    public function clearAll(): int
    {
        return $this->deleteByPattern(
            'ncache:*'
        );
    }

    private function deleteByPattern(
        string $pattern
    ): int {
        $redis = $this->conn()->connect();

        $iterator = null;
        $count = 0;

        do {
            $keys = $redis->scan(
                $iterator,
                $pattern,
                100
            );

            if ($keys === false || $keys === []) {
                continue;
            }

            $count += $redis->del($keys);
        } while ($iterator !== 0);

        return $count;
    }

    public function getFile(): ?string
    {
        return null;
    }
}