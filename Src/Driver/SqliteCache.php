<?php

declare(strict_types=1);

namespace NCache\Driver;

use NCache\Config\Connection\SQLitePdo;
use NCache\Core\Hash;
use NCache\Exceptions\InvalidCacheArgumentException;

final class SqliteCache extends CacheDriver
{
    private ?SQLitePdo $connection = null;

    private function conn(): SQLitePdo
    {
        return $this->connection ??=
            new SQLitePdo(
                $this->buildFile()
            );
    }

    private function table(): string
    {
        $t = $this->item->getDir();
        if ($t === null) {
            throw new InvalidCacheArgumentException(
                'SQLite cache requires a directory.'
            );
        }
        return 'cache_' . (new Hash($t))->get();
    }

    private function ensureTable(): void
    {
        $conn = $this->conn();
        $table = $this->table();
        $conn->execute(
            "CREATE TABLE IF NOT EXISTS {$table} (
                key TEXT PRIMARY KEY,
                data BLOB NULL
            )"
        );

        $this->ensureTableRegistry();

        $conn->execute(
            'INSERT OR IGNORE INTO cache_table(name) VALUES(:name)',
            [':name' => $table]
        );
    }

    private function ensureTableRegistry(): void
    {
        $this->conn()->execute(
            'CREATE TABLE IF NOT EXISTS cache_table(
            name TEXT PRIMARY KEY
            )'
        );
    }

    protected function format(): string
    {
        return serialize(
            $this->item->getData()
        );
    }

    public function buildFile(): string
    {
        return rtrim(
            $this->item->basePath(),
            '/\\'
        )
            . DIRECTORY_SEPARATOR
            . 'CacheDb'
            . DIRECTORY_SEPARATOR
            . 'nc.db';
    }

    public function save(): bool
    {
        $this->ensureTable();
        $table = $this->table();
        $this->conn()->execute(
            "INSERT INTO {$table} (key, data)
             VALUES (:key, :data)
             ON CONFLICT(key)
             DO UPDATE SET
                 data = excluded.data",
            [
                ':key' => $this->item->hashedKey(),
                ':data' => $this->format(),
            ]
        );

        return true;
    }

    private function tableExists(): bool
    {
        $this->ensureTableRegistry();
        $table = $this->table();
        return $this->conn()->get(
            'SELECT 1
         FROM cache_table
         WHERE name = :name
         LIMIT 1',
            [
                ':name' => $table,
            ]
        ) !== null;
    }

    public function exists(): bool
    {
        if (!$this->tableExists()) {
            return false;
        }

        return $this->conn()->get(
            "SELECT 1
             FROM {$this->table()}
             WHERE key = :key
             LIMIT 1",
            [
                ':key' => $this->item->hashedKey(),
            ]
        ) !== null;
    }

    /**
     * @return array<array-key, mixed>|null
     */
    public function get(): ?array
    {
        if (!$this->tableExists()) {
            return null;
        }

        $table = $this->table();

        $result = $this->conn()->get(
            "SELECT data
         FROM {$table}
         WHERE key = :key
         LIMIT 1",
            [
                ':key' => $this->item->hashedKey(),
            ]
        );

        if ($result === null) {
            return null;
        }

        $raw = $result['data'] ?? null;

        if (!\is_string($raw)) {
            throw new InvalidCacheArgumentException(
                'SQLite cache data must be a serialized string.'
            );
        }

        $data = unserialize(
            $raw,
            ['allowed_classes' => false]
        );

        if (!\is_array($data)) {
            throw new InvalidCacheArgumentException(
                'Invalid SQLite cache data.'
            );
        }

        return $data;
    }

    public function delete(): bool
    {
        if (!$this->tableExists()) {
            return true;
        }

        $this->conn()->execute(
            "DELETE FROM {$this->table()}
             WHERE key = :key",
            [
                ':key' => $this->item->hashedKey(),
            ]
        );

        return true;
    }

    public function clear(): int
    {
        $dir = $this->item->getDir();
        if ($dir === null || $dir === '') {
            return $this->clearAll();
        }

        if (!$this->tableExists()) {
            return 0;
        }

        $table = $this->table();

        $result = $this->conn()->get(
            "SELECT COUNT(*) AS total
         FROM {$table}"
        );

        if ($result === null) {
            return 0;
        }

        $total = $result['total'] ?? null;

        if (
            !\is_int($total)
            && !\is_string($total)
        ) {
            throw new InvalidCacheArgumentException(
                'SQLite cache count must be numeric.'
            );
        }

        if (!is_numeric($total)) {
            throw new InvalidCacheArgumentException(
                'SQLite cache count must be numeric.'
            );
        }

        $count = (int) $total;

        if ($count > 0) {
            $this->conn()->execute(
                "DELETE FROM {$table}"
            );
        }

        return $count;
    }

    public function clearAll(): int
    {
        $this->ensureTableRegistry();

        $tables = $this->conn()->getAll(
            'SELECT name FROM cache_table'
        );

        $count = 0;

        foreach ($tables as $row) {
            $table = $row['name'] ?? null;

            if (
                !\is_string($table)
                || !$this->isValidTableName($table)
            ) {
                continue;
            }

            $result = $this->conn()->get(
                "SELECT COUNT(*) AS total FROM {$table}"
            );

            $total = $result['total'] ?? 0;

            if (is_numeric($total)) {
                $count += (int) $total;
            }

            $this->conn()->execute(
                "DELETE FROM {$table}"
            );
        }

        return $count;
    }

    private function isValidTableName(string $table): bool
    {
        return preg_match(
            '/^cache_[a-f0-9]+$/',
            $table
        ) === 1;
    }

    public function getFile(): ?string
    {
        return null;
    }
}
