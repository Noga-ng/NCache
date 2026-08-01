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
        private readonly string $database
    ) {}

    /**
     * @throws RuntimeException
     * @return PDO
     */
    public function connect(): PDO
    {
        if ($this->pdo !== null) {
            return $this->pdo;
        }

        try {
           
            $pdo = new PDO(
                "sqlite:{$this->database}",
                options: [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]
            );

            $this->configure($pdo);
            $this->pdo = $pdo;
            
            return $this->pdo;

        } catch (PDOException $exception) {
            $this->pdo = null;

            throw new RuntimeException(
                'Failed to connect to SQLite.',
                previous: $exception
            );
        }
    }

    private function configure(PDO $pdo): void
    {
        $pdo->exec('PRAGMA foreign_keys = ON');
        $pdo->exec('PRAGMA journal_mode = WAL');
        $pdo->exec('PRAGMA synchronous = NORMAL');
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
     * @return PDOStatement
     */
    public function execute(
        string $sql,
        array $params = []
    ):PDOStatement {
        try {
            $statement = $this->connect()->prepare($sql);
            $statement->execute($params);

            return $statement;
        } catch (PDOException $exception) {
            throw new RuntimeException(
                'SQLite query execution failed.',
                previous: $exception
            );
        }
    }

    /**
     * @param array<array-key,mixed> $params
     * @return PDOStatement
     */
    public function create(
        string $sql,
        array $params = []
        ):PDOStatement{
        return $this->execute($sql,$params);
    }

    /**
     * @param array<array-key, mixed> $params
     * @return array<mixed>
     */
    public function getAll(
        string $sql,
        array $params = [],
        int $fetchMode = PDO::FETCH_ASSOC
    ): array {

        return $this
            ->execute($sql, $params)
            ->fetchAll($fetchMode);
    }

    /**
     * @param array<array-key, mixed> $params
     */
    public function get(
        string $sql,
        array $params = [],
        int $fetchMode = PDO::FETCH_ASSOC
    ): mixed {
        $result = $this
            ->execute($sql, $params)
            ->fetch($fetchMode);

        return $result === false ? null : $result;
    }

    /**
     * @template T
     * @param callable(self): T $callback
     * @return T
     */
    public function transaction(callable $callback): mixed
    {
        $pdo = $this->connect();

        try {
            $pdo->beginTransaction();

            $result = $callback($this);

            $pdo->commit();

            return $result;
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            throw new RuntimeException(
                'SQLite transaction failed.',
                previous: $exception
            );
        }
    }

    public function lastId(): string|false
    {
        return $this->connect()->lastInsertId();
    }
}