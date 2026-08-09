<?php

declare(strict_types=1);

namespace NCache\Tests\Units\Config\Connection;

use NCache\Config\Connection\SQLitePdo;
use NCache\Tests\TestsUnit\TestsUnit;
use RuntimeException;

final class SQLitePdoTest extends TestsUnit
{
    private string $database;

    protected function setUp(): void
    {
        parent::setUp();

        $this->directory('ncache-sqlite-pdo-');

        $this->database = $this->directory
            . DIRECTORY_SEPARATOR
            . 'db'
            . DIRECTORY_SEPARATOR
            . 'test.db';
    }

    public function testConnectCreatesDatabaseDirectoryAndConnection(): void
    {
        $sqlite = new SQLitePdo($this->database);

        $pdo = $sqlite->connect();

        self::assertTrue($sqlite->isConnected());
        self::assertFileExists($this->database);
        self::assertSame($pdo, $sqlite->connect());
    }

    public function testDisconnectResetsConnectionState(): void
    {
        $sqlite = new SQLitePdo($this->database);

        $sqlite->connect();

        self::assertTrue($sqlite->isConnected());

        $sqlite->disconnect();

        self::assertFalse($sqlite->isConnected());
    }

    public function testExecuteCanCreateTable(): void
    {
        $sqlite = new SQLitePdo($this->database);

        $statement = $sqlite->execute(
            'CREATE TABLE test (
                id INTEGER PRIMARY KEY,
                name TEXT NOT NULL
            )'
        );

        self::assertNotFalse($statement);
    }

    public function testExecuteSupportsParameters(): void
    {
        $sqlite = new SQLitePdo($this->database);

        $sqlite->execute(
            'CREATE TABLE users (
                id INTEGER PRIMARY KEY,
                name TEXT NOT NULL
            )'
        );

        $sqlite->execute(
            'INSERT INTO users (name)
             VALUES (:name)',
            [
                ':name' => 'Noga',
            ]
        );

        $result = $sqlite->get(
            'SELECT name
             FROM users
             WHERE name = :name',
            [
                ':name' => 'Noga',
            ]
        );

        self::assertNotNull($result);
        self::assertSame('Noga', $result['name']);
    }

    public function testGetReturnsNullWhenNothingMatches(): void
    {
        $sqlite = new SQLitePdo($this->database);

        $sqlite->execute(
            'CREATE TABLE users (
                id INTEGER PRIMARY KEY,
                name TEXT NOT NULL
            )'
        );

        self::assertNull(
            $sqlite->get(
                'SELECT *
                 FROM users
                 WHERE id = :id',
                [
                    ':id' => 999,
                ]
            )
        );
    }

    public function testGetAllReturnsAllRows(): void
    {
        $sqlite = new SQLitePdo($this->database);

        $sqlite->execute(
            'CREATE TABLE users (
                id INTEGER PRIMARY KEY,
                name TEXT NOT NULL
            )'
        );

        $sqlite->execute(
            'INSERT INTO users (name)
             VALUES (:name)',
            [':name' => 'Noga']
        );

        $sqlite->execute(
            'INSERT INTO users (name)
             VALUES (:name)',
            [':name' => 'Germainio']
        );

        $rows = $sqlite->getAll(
            'SELECT name
             FROM users
             ORDER BY id'
        );

        self::assertCount(2, $rows);
        self::assertSame('Noga', $rows[0]['name']);
        self::assertSame('Germainio', $rows[1]['name']);
    }

    public function testTransactionCommitsOnSuccess(): void
    {
        $sqlite = new SQLitePdo($this->database);

        $sqlite->execute(
            'CREATE TABLE users (
                id INTEGER PRIMARY KEY,
                name TEXT NOT NULL
            )'
        );

        $sqlite->transaction(
            static function (SQLitePdo $db): void {
                $db->execute(
                    'INSERT INTO users (name)
                     VALUES (:name)',
                    [
                        ':name' => 'Noga',
                    ]
                );
            }
        );

        $result = $sqlite->get(
            'SELECT name FROM users LIMIT 1'
        );

        self::assertNotNull($result);
        self::assertSame('Noga', $result['name']);
    }

    public function testTransactionRollsBackOnFailure(): void
    {
        $sqlite = new SQLitePdo($this->database);

        $sqlite->execute(
            'CREATE TABLE users (
                id INTEGER PRIMARY KEY,
                name TEXT NOT NULL
            )'
        );

        try {
            $sqlite->transaction(
                static function (SQLitePdo $db): void {
                    $db->execute(
                        'INSERT INTO users (name)
                         VALUES (:name)',
                        [
                            ':name' => 'Noga',
                        ]
                    );

                    throw new RuntimeException(
                        'force rollback'
                    );
                }
            );
        } catch (RuntimeException) {
            // attendu
        }

        $rows = $sqlite->getAll(
            'SELECT * FROM users'
        );

        self::assertSame([], $rows);
    }

    public function testInvalidSqlThrowsRuntimeException(): void
    {
        $sqlite = new SQLitePdo($this->database);

        $this->expectException(
            RuntimeException::class
        );

        $sqlite->execute(
            'THIS IS NOT VALID SQL'
        );
    }
}