<?php

declare(strict_types=1);

namespace NCache\Tests\Units\Driver;

use Memcached;
use NCache\Config\Connection\MCached;
use NCache\Driver\MemCache;
use NCache\Tests\TestsUnit\TestsUnit;

use function PHPUnit\Framework\assertTrue;

final class MemCacheTest extends TestsUnit
{
    private Memcached $client;

    protected function setUp(): void
    {
        parent::setUp();
        $this->directory('memcached-driver');
        $this->client = (new MCached())->connect();

        assertTrue($this->client->flush());
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->client->flush();
        $this->removeDirectory($this->directory);
    }

    public function testSaveAndGet(): void
    {
        $item = $this->createMemCachedItem(
            'memcached-save',
        );

        $item->setDir('users');
        $item->setData([
            'name' => 'NCache',
        ]);

        $driver = new MemCache($item);

        self::assertTrue(
            $driver->save(),
        );

        self::assertSame(
            ['name' => 'NCache'],
            $driver->get(),
        );
    }

    public function testExistsReturnsFalseBeforeSave(): void
    {
        $item = $this->createMemCachedItem(
            'missing',
        );

        $item->setDir('users');

        $driver = new MemCache($item);

        self::assertFalse(
            $driver->exists(),
        );
    }

    public function testExistsReturnsTrueAfterSave(): void
    {
        $item = $this->createMemCachedItem(
            'existing',
        );

        $item->setDir('users');
        $item->setData(['id' => 1]);

        $driver = new MemCache($item);

        self::assertTrue($driver->save());
        self::assertTrue($driver->exists());
    }

    public function testSaveReplacesExistingValue(): void
    {
        $item = $this->createMemCachedItem(
            'replace',
        );

        $item->setDir('users');

        $item->setData([
            'version' => 1,
        ]);

        $driver = new MemCache($item);

        self::assertTrue($driver->save());

        $item->setData([
            'version' => 2,
        ]);

        self::assertTrue($driver->save());

        self::assertSame(
            ['version' => 2],
            $driver->get(),
        );
    }

    public function testSameKeyInDifferentDirectoriesIsIsolated(): void
    {
        $firstItem = $this->createMemCachedItem(
            'same-key',
        );

        $secondItem = $this->createMemCachedItem(
            'same-key',
        );

        $firstItem->setDir('users');
        $secondItem->setDir('admins');

        $firstItem->setData([
            'source' => 'users',
        ]);

        $secondItem->setData([
            'source' => 'admins',
        ]);

        $first = new MemCache($firstItem);
        $second = new MemCache($secondItem);

        self::assertTrue($first->save());
        self::assertTrue($second->save());

        self::assertSame(
            ['source' => 'users'],
            $first->get(),
        );

        self::assertSame(
            ['source' => 'admins'],
            $second->get(),
        );
    }

    public function testDeleteRemovesCurrentCache(): void
    {
        $item = $this->createMemCachedItem(
            'delete',
        );

        $item->setDir('users');
        $item->setData(['id' => 1]);

        $driver = new MemCache($item);

        self::assertTrue($driver->save());
        self::assertTrue($driver->exists());

        self::assertTrue(
            $driver->delete(),
        );

        self::assertFalse(
            $driver->exists(),
        );
    }

    public function testDeleteIsIdempotent(): void
    {
        $item = $this->createMemCachedItem(
            'delete-twice',
        );

        $item->setDir('users');

        $driver = new MemCache($item);

        self::assertTrue($driver->delete());
        self::assertTrue($driver->delete());
    }

    public function testClearRemovesOnlyCurrentDirectory(): void
    {
        $user1 = $this->createMemCachedItem('u1');
        $user2 = $this->createMemCachedItem('u2');
        $admin = $this->createMemCachedItem('a1');

        $user1->setDir('users');
        $user2->setDir('users');
        $admin->setDir('admins');

        $user1->setData(['id' => 1]);
        $user2->setData(['id' => 2]);
        $admin->setData(['id' => 3]);

        $first = new MemCache($user1);
        $second = new MemCache($user2);
        $third = new MemCache($admin);

        self::assertTrue($first->save());
        self::assertTrue($second->save());
        self::assertTrue($third->save());

        self::assertSame(
            2,
            $first->clear(),
        );

        self::assertFalse($first->exists());
        self::assertFalse($second->exists());

        self::assertTrue(
            $third->exists(),
        );
    }

    public function testClearAllRemovesEveryNCacheEntry(): void
    {
        $firstItem = $this->createMemCachedItem('first');
        $secondItem = $this->createMemCachedItem('second');
        $thirdItem = $this->createMemCachedItem('third');

        $firstItem->setDir('users');
        $secondItem->setDir('products');
        $thirdItem->setDir('sessions');

        $firstItem->setData(['id' => 1]);
        $secondItem->setData(['id' => 2]);
        $thirdItem->setData(['id' => 3]);

        $first = new MemCache($firstItem);
        $second = new MemCache($secondItem);
        $third = new MemCache($thirdItem);

        self::assertTrue($first->save());
        self::assertTrue($second->save());
        self::assertTrue($third->save());

        self::assertSame(
            3,
            $first->clearAll(),
        );

        self::assertFalse($first->exists());
        self::assertFalse($second->exists());
        self::assertFalse($third->exists());
    }

    public function testMixedDataIsPreserved(): void
    {
        $data = [
            'string' => 'NCache',
            'int' => 10,
            'float' => 3.14,
            'true' => true,
            'false' => false,
            'null' => null,
            'nested' => [
                'a' => 1,
                'b' => 2,
            ],
        ];

        $item = $this->createMemCachedItem(
            'mixed',
        );

        $item->setDir('mixed');
        $item->setData($data);

        $driver = new MemCache($item);

        self::assertTrue($driver->save());

        self::assertSame(
            $data,
            $driver->get(),
        );
    }

    public function testNamespaceIndexDoesNotDuplicateKey(): void
    {
        $item = $this->createMemCachedItem(
            'duplicate-index',
        );

        $item->setDir('users');
        $item->setData(['version' => 1]);

        $driver = new MemCache($item);

        self::assertTrue($driver->save());

        $item->setData([
            'version' => 2,
        ]);

        self::assertTrue($driver->save());

        self::assertSame(
            1,
            $driver->clear(),
        );
    }

    public function testGetFileReturnsNull(): void
    {
        $item = $this->createMemCachedItem(
            'file',
        );

        $item->setDir('users');

        $driver = new MemCache($item);

        self::assertNull(
            $driver->getFile(),
        );
    }
}
