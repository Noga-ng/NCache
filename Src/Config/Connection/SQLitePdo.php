<?php

declare(strict_types=1);

namespace NCache\Config\Connection;

use PDO;
use PDOException;
use PDOStatement;
use RuntimeException;
use Throwable;

final class SQLitePdo
{
    private ?PDO $pdo = null;

    public function __construct(
        private readonly string $database,
    ) {
    }

    public function connect(): PDO
    {
        if ($this->pdo !== null) {
            return $this->pdo;
        }

        $this->ensureDirectory();

        try {
            $pdo = new PDO(
                "sqlite:{$this->database}",
                options: [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                ],
            );

            $this->configure($pdo);

            $this->pdo = $pdo;

            return $this->pdo;
        } catch (PDOException $exception) {
            $this->pdo = null;

            throw new RuntimeException(
                'Failed to connect to SQLite.',
                previous: $exception,
            );
        }
    }

    private function configure(PDO $pdo): void
    {
        $pdo->exec('PRAGMA foreign_keys = ON');
        $pdo->exec('PRAGMA journal_mode = WAL');
        $pdo->exec('PRAGMA synchronous = NORMAL');
        $pdo->exec('PRAGMA busy_timeout = 5000');
    }

    public function isConnected(): bool
    {
        return $this->pdo !== null;
    }

    public function disconnect(): void
    {
        $this->pdo = null;
    }

    /**
     * @param array<array-key, mixed> $params
     */
    public function execute(string $sql, array $params = []): PDOStatement
    {
        try {
            $statement = $this
                ->connect()
                ->prepare($sql);

            $statement->execute($params);

            return $statement;
        } catch (PDOException $exception) {
            throw new RuntimeException(
                'SQLite query execution failed.',
                previous: $exception,
            );
        }
    }

    /**
     * @param array<array-key, mixed> $params
     * @return list<array<string, mixed>>
     */
    public function getAll(string $sql, array $params = []): array
    {
        $result = $this
            ->execute($sql, $params)
            ->fetchAll(PDO::FETCH_ASSOC);

        /** @var list<array<string, mixed>> $result */
        return $result;
    }

    /**
     * @param array<array-key, mixed> $params
     * @return array<string, mixed>|null
     */
    public function get(string $sql, array $params = []): ?array
    {
        $result = $this
            ->execute($sql, $params)
            ->fetch(PDO::FETCH_ASSOC);

        if ($result === false) {
            return null;
        }

        /** @var array<string, mixed> $result */
        return $result;
    }

    /**
     * @template T
     * @param callable(self): T $callback
     * @return T
     */
    public function transaction(callable $callback): mixed
    {
        $pdo = $this->connect();

        if ($pdo->inTransaction()) {
            throw new RuntimeException(
                'A SQLite transaction is already active.',
            );
        }

        try {
            $pdo->beginTransaction();

            $result = $callback($this);

            $pdo->commit();

            return $result;
        } catch (Throwable $exception) {
            $pdo->rollBack();

            throw new RuntimeException(
                'SQLite transaction failed.',
                previous: $exception,
            );
        }
    }

    private function ensureDirectory(): void
    {
        $directory = dirname(
            $this->database,
        );

        if (is_dir($directory)) {
            return;
        }

        if (
            !mkdir($directory, 0o777, true)
            && !is_dir($directory)
        ) {
            throw new RuntimeException(
                "Unable to create SQLite directory: {$directory}",
            );
        }
    }
}
