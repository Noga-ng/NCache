<?php

declare(strict_types=1);

namespace NCache\Tests\Units\Driver;

use NCache\Driver\SqliteCache;
use NCache\Tests\TestsUnit\TestsUnit;

final class SqliteCacheTest extends TestsUnit
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->directory(
            'ncache-sqlite-driver-'
        );
    }

    public function testSaveCreatesDatabaseFile(): void
    {
        $item = $this->createSQLiteItem(
            'sqlite-create'
        );

        $item->setData([
            'name' => 'Noga',
        ]);

        $driver = new SqliteCache($item);

        self::assertTrue(
            $driver->save()
        );

        self::assertFileExists(
            $driver->getFile()
        );
    }

    public function testExistsReturnsFalseBeforeSave(): void
    {
        $item = $this->createSQLiteItem(
            'sqlite-missing'
        );

        $driver = new SqliteCache($item);

        self::assertFalse(
            $driver->exists()
        );
    }

    public function testExistsReturnsTrueAfterSave(): void
    {
        $item = $this->createSQLiteItem(
            'sqlite-existing'
        );

        $item->setData([
            'id' => 1,
        ]);

        $driver = new SqliteCache($item);

        self::assertTrue($driver->save());
        self::assertTrue($driver->exists());
    }

    public function testGetReturnsSavedData(): void
    {
        $data = [
            'id' => 1,
            'name' => 'Noga',
            'active' => true,
        ];

        $item = $this->createSQLiteItem(
            'sqlite-get'
        );

        $item->setData($data);

        $driver = new SqliteCache($item);

        self::assertTrue(
            $driver->save()
        );

        self::assertSame(
            $data,
            $driver->get()
        );
    }

    public function testGetReturnsNullWhenCacheDoesNotExist(): void
    {
        $driver = new SqliteCache(
            $this->createSQLiteItem(
                'sqlite-not-found'
            )
        );

        self::assertNull(
            $driver->get()
        );
    }

    public function testSaveUsesUpsertForExistingKey(): void
    {
        $item = $this->createSQLiteItem(
            'sqlite-upsert'
        );

        $item->setData([
            'version' => 1,
        ]);

        $driver = new SqliteCache($item);

        self::assertTrue($driver->save());

        $item->setData([
            'version' => 2,
        ]);

        self::assertTrue($driver->save());

        self::assertSame(
            ['version' => 2],
            $driver->get()
        );
    }

    public function testUpsertDoesNotCreateDuplicateRows(): void
    {
        $item = $this->createSQLiteItem(
            'sqlite-single-row'
        );

        $item->setData([
            'version' => 1,
        ]);

        $driver = new SqliteCache($item);

        self::assertTrue($driver->save());

        $item->setData([
            'version' => 2,
        ]);

        self::assertTrue($driver->save());

        self::assertSame(
            1,
            $driver->clear()
        );
    }

    public function testMultipleKeysCanCoexist(): void
    {
        $firstItem = $this->createSQLiteItem(
            'sqlite-first'
        );

        $secondItem = $this->createSQLiteItem(
            'sqlite-second'
        );

        $firstItem->setData([
            'id' => 1,
        ]);

        $secondItem->setData([
            'id' => 2,
        ]);

        $first = new SqliteCache($firstItem);
        $second = new SqliteCache($secondItem);

        self::assertTrue($first->save());
        self::assertTrue($second->save());

        self::assertSame(
            ['id' => 1],
            $first->get()
        );

        self::assertSame(
            ['id' => 2],
            $second->get()
        );

        self::assertTrue($first->exists());
        self::assertTrue($second->exists());
    }

    public function testDeleteRemovesOnlyCurrentKey(): void
    {
        $firstItem = $this->createSQLiteItem(
            'sqlite-delete-first'
        );

        $secondItem = $this->createSQLiteItem(
            'sqlite-delete-second'
        );

        $firstItem->setData([
            'id' => 1,
        ]);

        $secondItem->setData([
            'id' => 2,
        ]);

        $first = new SqliteCache($firstItem);
        $second = new SqliteCache($secondItem);

        self::assertTrue($first->save());
        self::assertTrue($second->save());

        self::assertTrue(
            $first->delete()
        );

        self::assertFalse(
            $first->exists()
        );

        self::assertTrue(
            $second->exists()
        );
    }

    public function testDeleteIsIdempotent(): void
    {
        $item = $this->createSQLiteItem(
            'sqlite-delete-twice'
        );

        $item->setData([
            'id' => 1,
        ]);

        $driver = new SqliteCache($item);

        self::assertTrue($driver->save());

        self::assertTrue($driver->delete());
        self::assertTrue($driver->delete());

        self::assertFalse(
            $driver->exists()
        );
    }

    public function testClearReturnsNumberOfDeletedCaches(): void
    {
        $firstItem = $this->createSQLiteItem(
            'sqlite-clear-first'
        );

        $secondItem = $this->createSQLiteItem(
            'sqlite-clear-second'
        );

        $thirdItem = $this->createSQLiteItem(
            'sqlite-clear-third'
        );

        $firstItem->setData(['id' => 1]);
        $secondItem->setData(['id' => 2]);
        $thirdItem->setData(['id' => 3]);

        $first = new SqliteCache($firstItem);
        $second = new SqliteCache($secondItem);
        $third = new SqliteCache($thirdItem);

        self::assertTrue($first->save());
        self::assertTrue($second->save());
        self::assertTrue($third->save());

        self::assertSame(
            3,
            $first->clear()
        );

        self::assertFalse($first->exists());
        self::assertFalse($second->exists());
        self::assertFalse($third->exists());
    }

    public function testClearReturnsZeroWhenDatabaseIsEmpty(): void
    {
        $driver = new SqliteCache(
            $this->createSQLiteItem(
                'sqlite-empty'
            )
        );

        self::assertSame(
            0,
            $driver->clear()
        );
    }

    public function testMixedDataIsPreserved(): void
    {
        $data = [
            'string' => 'NCache',
            'integer' => 42,
            'float' => 15.75,
            'true' => true,
            'false' => false,
            'null' => null,
            'nested' => [
                'language' => 'PHP',
                'versions' => [
                    8.1,
                    8.2,
                    8.3,
                    8.4,
                ],
            ],
        ];

        $item = $this->createSQLiteItem(
            'sqlite-mixed'
        );

        $item->setData($data);

        $driver = new SqliteCache($item);

        self::assertTrue($driver->save());

        self::assertSame(
            $data,
            $driver->get()
        );
    }

    public function testNumericArrayKeysArePreserved(): void
    {
        $data = [
            10 => 'PHP',
            20 => 'JavaScript',
            50 => 'Go',
        ];

        $item = $this->createSQLiteItem(
            'sqlite-numeric-keys'
        );

        $item->setData($data);

        $driver = new SqliteCache($item);

        self::assertTrue($driver->save());

        self::assertSame(
            $data,
            $driver->get()
        );
    }

    public function testGetFileReturnsInternalDatabasePath(): void
    {
        $driver = new SqliteCache(
            $this->createSQLiteItem(
                'sqlite-path'
            )
        );

        self::assertSame(
            $this->directory
                . DIRECTORY_SEPARATOR
                . 'CacheDb'
                . DIRECTORY_SEPARATOR
                . 'nc.db',
            $driver->getFile()
        );
    }
}