<?php

declare(strict_types=1);

namespace NCache\Tests\Units\Driver;

use NCache\Config\Connection\RedisConn;
use NCache\Driver\RedisCache;
use NCache\Tests\TestsUnit\TestsUnit;
use Redis;

final class RedisCacheTest extends TestsUnit
{
    private Redis $client;

    protected function setUp(): void
    {
        parent::setUp();
        $this->directory("redis-driver");
        $this->client = new RedisConn()->connect();

        self::assertTrue(
            $this->client->isConnected()
        );

        $this->clearNCacheKeys();
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->directory);
        $this->clearNCacheKeys();
        $this->client->close();

        parent::tearDown();
    }

    public function testSaveAndGet(): void
    {
        $item = $this->createRedisItem(
            'redis-save'
        );

        $item->setDir('users');

        $item->setData([
            'name' => 'NCache',
        ]);

        $driver = new RedisCache($item);

        self::assertTrue(
            $driver->save()
        );

        self::assertSame(
            ['name' => 'NCache'],
            $driver->get()
        );
    }

    public function testExistsReturnsFalseBeforeSave(): void
    {
        $item = $this->createRedisItem(
            'redis-missing'
        );

        $item->setDir('users');

        $driver = new RedisCache($item);

        self::assertFalse(
            $driver->exists()
        );
    }

    public function testExistsReturnsTrueAfterSave(): void
    {
        $item = $this->createRedisItem(
            'redis-existing'
        );

        $item->setDir('users');
        $item->setData(['id' => 1]);

        $driver = new RedisCache($item);

        self::assertTrue($driver->save());
        self::assertTrue($driver->exists());
    }

    public function testSaveReplacesExistingValue(): void
    {
        $item = $this->createRedisItem(
            'redis-replace'
        );

        $item->setDir('users');

        $item->setData([
            'version' => 1,
        ]);

        $driver = new RedisCache($item);

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

    public function testSameKeyInDifferentDirectoriesIsIsolated(): void
    {
        $firstItem = $this->createRedisItem(
            'same-key'
        );

        $secondItem = $this->createRedisItem(
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

        $first = new RedisCache($firstItem);
        $second = new RedisCache($secondItem);

        self::assertTrue($first->save());
        self::assertTrue($second->save());

        self::assertSame(
            ['source' => 'users'],
            $first->get()
        );

        self::assertSame(
            ['source' => 'admins'],
            $second->get()
        );
    }

    public function testDeleteRemovesCurrentCache(): void
    {
        $item = $this->createRedisItem(
            'redis-delete'
        );

        $item->setDir('users');
        $item->setData(['id' => 1]);

        $driver = new RedisCache($item);

        self::assertTrue($driver->save());
        self::assertTrue($driver->exists());

        self::assertTrue(
            $driver->delete()
        );

        self::assertFalse(
            $driver->exists()
        );
    }

    public function testDeleteIsIdempotent(): void
    {
        $item = $this->createRedisItem(
            'redis-delete-twice'
        );

        $item->setDir('users');

        $driver = new RedisCache($item);

        self::assertTrue(
            $driver->delete()
        );

        self::assertTrue(
            $driver->delete()
        );
    }

    public function testClearRemovesOnlyCurrentDirectory(): void
    {
        $user1 = $this->createRedisItem('u1');
        $user2 = $this->createRedisItem('u2');
        $admin = $this->createRedisItem('a1');

        $user1->setDir('users');
        $user2->setDir('users');
        $admin->setDir('admins');

        $user1->setData(['id' => 1]);
        $user2->setData(['id' => 2]);
        $admin->setData(['id' => 3]);

        $first = new RedisCache($user1);
        $second = new RedisCache($user2);
        $third = new RedisCache($admin);

        self::assertTrue($first->save());
        self::assertTrue($second->save());
        self::assertTrue($third->save());

        self::assertSame(
            2,
            $first->clear()
        );

        self::assertFalse($first->exists());
        self::assertFalse($second->exists());

        self::assertTrue(
            $third->exists()
        );
    }

    public function testClearAllRemovesEveryNCacheEntry(): void
    {
        $firstItem = $this->createRedisItem('first');
        $secondItem = $this->createRedisItem('second');
        $thirdItem = $this->createRedisItem('third');

        $firstItem->setDir('users');
        $secondItem->setDir('products');
        $thirdItem->setDir('sessions');

        $firstItem->setData(['id' => 1]);
        $secondItem->setData(['id' => 2]);
        $thirdItem->setData(['id' => 3]);

        $first = new RedisCache($firstItem);
        $second = new RedisCache($secondItem);
        $third = new RedisCache($thirdItem);

        self::assertTrue($first->save());
        self::assertTrue($second->save());
        self::assertTrue($third->save());

        self::assertSame(
            3,
            $first->clearAll()
        );

        self::assertFalse($first->exists());
        self::assertFalse($second->exists());
        self::assertFalse($third->exists());
    }

    public function testMixedDataIsPreserved(): void
    {
        $data = [
            'string' => 'NCache',
            'int' => 42,
            'float' => 12.5,
            'true' => true,
            'false' => false,
            'null' => null,
            'nested' => [
                'redis' => true,
                'items' => [1, 2, 3],
            ],
        ];

        $item = $this->createRedisItem(
            'redis-mixed'
        );

        $item->setDir('mixed');
        $item->setData($data);

        $driver = new RedisCache($item);

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
        $item = $this->createRedisItem(
            'redis-not-found'
        );

        $item->setDir('users');

        $driver = new RedisCache($item);

        self::assertNull(
            $driver->get()
        );
    }

    public function testGetFileReturnsNull(): void
    {
        $item = $this->createRedisItem(
            'redis-file'
        );

        $item->setDir('users');

        $driver = new RedisCache($item);

        self::assertNull(
            $driver->getFile()
        );
    }

    private function clearNCacheKeys(): void
    {
        $iterator = null;

        do {
            $keys = $this->client->scan(
                $iterator,
                'ncache:*',
                100
            );

            if ($keys === false || $keys === []) {
                continue;
            }

            $this->client->del($keys);
        } while ($iterator !== 0);
    }
}
