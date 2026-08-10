<?php

declare(strict_types=1);

namespace NCache\Tests\Units\Driver;

use NCache\Driver\SqliteCache;
use NCache\Exceptions\InvalidCacheArgumentException;
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

        $item->setDir('users');

        $item->setData([
            'name' => 'Noga',
        ]);

        $driver = new SqliteCache($item);

        self::assertTrue(
            $driver->save()
        );

        self::assertFileExists(
            $this->directory
                . DIRECTORY_SEPARATOR
                . 'CacheDb'
                . DIRECTORY_SEPARATOR
                . 'nc.db'
        );
    }

    public function testSQLiteRequiresDirectory(): void
    {
        $item = $this->createSQLiteItem(
            'sqlite-without-directory'
        );

        $item->setData([
            'id' => 1,
        ]);

        $driver = new SqliteCache($item);

        $this->expectException(
            InvalidCacheArgumentException::class
        );

        $driver->save();
    }

    public function testExistsReturnsFalseBeforeSave(): void
    {
        $item = $this->createSQLiteItem(
            'sqlite-missing'
        );

        $item->setDir('users');

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

        $item->setDir('users');

        $item->setData([
            'id' => 1,
        ]);

        $driver = new SqliteCache($item);

        self::assertTrue(
            $driver->save()
        );

        self::assertTrue(
            $driver->exists()
        );
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

        $item->setDir('users');
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
        $item = $this->createSQLiteItem(
            'sqlite-not-found'
        );

        $item->setDir('users');

        $driver = new SqliteCache($item);

        self::assertNull(
            $driver->get()
        );
    }

    public function testSaveUsesUpsertForExistingKey(): void
    {
        $item = $this->createSQLiteItem(
            'sqlite-upsert'
        );

        $item->setDir('users');

        $item->setData([
            'version' => 1,
        ]);

        $driver = new SqliteCache($item);

        self::assertTrue(
            $driver->save()
        );

        $item->setData([
            'version' => 2,
        ]);

        self::assertTrue(
            $driver->save()
        );

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

        $item->setDir('users');

        $item->setData([
            'version' => 1,
        ]);

        $driver = new SqliteCache($item);

        self::assertTrue(
            $driver->save()
        );

        $item->setData([
            'version' => 2,
        ]);

        self::assertTrue(
            $driver->save()
        );

        self::assertSame(
            1,
            $driver->clear()
        );
    }

    public function testMultipleKeysCanCoexistInSameDirectory(): void
    {
        $firstItem = $this->createSQLiteItem(
            'sqlite-first'
        );

        $secondItem = $this->createSQLiteItem(
            'sqlite-second'
        );

        $firstItem->setDir('users');
        $secondItem->setDir('users');

        $firstItem->setData([
            'id' => 1,
        ]);

        $secondItem->setData([
            'id' => 2,
        ]);

        $first = new SqliteCache($firstItem);
        $second = new SqliteCache($secondItem);

        self::assertTrue(
            $first->save()
        );

        self::assertTrue(
            $second->save()
        );

        self::assertSame(
            ['id' => 1],
            $first->get()
        );

        self::assertSame(
            ['id' => 2],
            $second->get()
        );

        self::assertTrue(
            $first->exists()
        );

        self::assertTrue(
            $second->exists()
        );
    }

    public function testSameKeyInDifferentDirectoriesIsIsolated(): void
    {
        $firstItem = $this->createSQLiteItem(
            'same-key'
        );

        $secondItem = $this->createSQLiteItem(
            'same-key'
        );

        $firstItem->setDir('users');
        $secondItem->setDir('admins');

        $firstItem->setData([
            'source' => 'users',
        ]);

        $secondItem->setData([
            'source' => 'admins',
        ]);

        $first = new SqliteCache($firstItem);
        $second = new SqliteCache($secondItem);

        self::assertTrue(
            $first->save()
        );

        self::assertTrue(
            $second->save()
        );

        self::assertSame(
            ['source' => 'users'],
            $first->get()
        );

        self::assertSame(
            ['source' => 'admins'],
            $second->get()
        );

        self::assertTrue(
            $first->exists()
        );

        self::assertTrue(
            $second->exists()
        );
    }

    public function testDeleteRemovesOnlyCurrentKey(): void
    {
        $firstItem = $this->createSQLiteItem(
            'sqlite-delete-first'
        );

        $secondItem = $this->createSQLiteItem(
            'sqlite-delete-second'
        );

        $firstItem->setDir('users');
        $secondItem->setDir('users');

        $firstItem->setData([
            'id' => 1,
        ]);

        $secondItem->setData([
            'id' => 2,
        ]);

        $first = new SqliteCache($firstItem);
        $second = new SqliteCache($secondItem);

        self::assertTrue(
            $first->save()
        );

        self::assertTrue(
            $second->save()
        );

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

        $item->setDir('users');

        $item->setData([
            'id' => 1,
        ]);

        $driver = new SqliteCache($item);

        self::assertTrue(
            $driver->save()
        );

        self::assertTrue(
            $driver->delete()
        );

        self::assertTrue(
            $driver->delete()
        );

        self::assertFalse(
            $driver->exists()
        );
    }

    public function testClearRemovesOnlyCurrentDirectory(): void
    {
        $user1 = $this->createSQLiteItem('u1');
        $user2 = $this->createSQLiteItem('u2');
        $admin = $this->createSQLiteItem('a1');

        $user1->setDir('users');
        $user2->setDir('users');
        $admin->setDir('admins');

        $user1->setData(['id' => 1]);
        $user2->setData(['id' => 2]);
        $admin->setData(['id' => 3]);

        $first = new SqliteCache($user1);
        $second = new SqliteCache($user2);
        $third = new SqliteCache($admin);

        self::assertTrue(
            $first->save()
        );

        self::assertTrue(
            $second->save()
        );

        self::assertTrue(
            $third->save()
        );

        self::assertSame(
            2,
            $first->clear()
        );

        self::assertFalse(
            $first->exists()
        );

        self::assertFalse(
            $second->exists()
        );

        self::assertTrue(
            $third->exists()
        );
    }

    public function testClearReturnsZeroWhenDirectoryIsEmpty(): void
    {
        $item = $this->createSQLiteItem(
            'sqlite-empty'
        );

        $item->setDir('empty');

        $driver = new SqliteCache($item);

        self::assertSame(
            0,
            $driver->clear()
        );
    }

    public function testClearAllRemovesEverySQLiteCache(): void
    {
        $firstItem = $this->createSQLiteItem(
            'first'
        );

        $secondItem = $this->createSQLiteItem(
            'second'
        );

        $thirdItem = $this->createSQLiteItem(
            'third'
        );

        $firstItem->setDir('users');
        $secondItem->setDir('products');
        $thirdItem->setDir('sessions');

        $firstItem->setData([
            'id' => 1,
        ]);

        $secondItem->setData([
            'id' => 2,
        ]);

        $thirdItem->setData([
            'id' => 3,
        ]);

        $first = new SqliteCache($firstItem);
        $second = new SqliteCache($secondItem);
        $third = new SqliteCache($thirdItem);

        self::assertTrue(
            $first->save()
        );

        self::assertTrue(
            $second->save()
        );

        self::assertTrue(
            $third->save()
        );

        self::assertSame(
            3,
            $first->clearAll()
        );

        self::assertFalse(
            $first->exists()
        );

        self::assertFalse(
            $second->exists()
        );

        self::assertFalse(
            $third->exists()
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

        $item->setDir('mixed');
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

        $item->setDir('languages');
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

    public function testGetFileReturnsNull(): void
    {
        $item = $this->createSQLiteItem(
            'sqlite-file'
        );

        $item->setDir('users');

        $driver = new SqliteCache($item);

        self::assertNull(
            $driver->getFile()
        );
    }
}
