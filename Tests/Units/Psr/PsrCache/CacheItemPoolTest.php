<?php

declare(strict_types=1);

namespace NCache\Tests\Units\Psr\PsrCache;

use NCache\Enum\CType;
use NCache\Psr\PsrCache\CacheItemPool;
use NCache\Psr\PsrCache\Exceptions\InvalidCacheArgumentException;
use NCache\Tests\TestsUnit\TestsUnit;
use Psr\Cache\CacheItemInterface;

final class CacheItemPoolTest extends TestsUnit
{
    private CacheItemPool $pool;

    protected function setUp(): void
    {
        parent::setUp();

        $this->directory(
            'ncache-psr6-',
        );

        $this->pool = new CacheItemPool(
            $this->configFile,
            'default',
            CType::JSON,
        );
    }

    public function testGetItemReturnsMissWhenCacheDoesNotExist(): void
    {
        $item = $this->pool->getItem(
            'missing',
        );

        self::assertInstanceOf(
            CacheItemInterface::class,
            $item,
        );

        self::assertSame(
            'missing',
            $item->getKey(),
        );

        self::assertFalse(
            $item->isHit(),
        );

        self::assertNull(
            $item->get(),
        );
    }

    public function testSaveStoresItem(): void
    {
        $item = $this->pool->getItem(
            'user.1',
        );

        $item->set([
            'id' => 1,
        ]);

        self::assertTrue(
            $this->pool->save(
                $item,
            ),
        );

        self::assertTrue(
            $this->pool->hasItem(
                'user.1',
            ),
        );
    }

    public function testGetItemReturnsHitAfterSave(): void
    {
        $item = $this->pool->getItem(
            'user.1',
        );

        $item->set([
            'id' => 1,
        ]);

        self::assertTrue(
            $this->pool->save(
                $item,
            ),
        );

        $stored = $this->pool->getItem(
            'user.1',
        );

        self::assertTrue(
            $stored->isHit(),
        );

        self::assertSame(
            [
                'id' => 1,
            ],
            $stored->get(),
        );
    }

    public function testGetItemsReturnsItemsIndexedByKey(): void
    {
        $first = $this->pool->getItem(
            'user.1',
        );

        $first->set([
            'id' => 1,
        ]);

        $second = $this->pool->getItem(
            'user.2',
        );

        $second->set([
            'id' => 2,
        ]);

        self::assertTrue(
            $this->pool->save(
                $first,
            ),
        );

        self::assertTrue(
            $this->pool->save(
                $second,
            ),
        );

        $items = $this->pool->getItems([
            'user.1',
            'user.2',
            'missing',
        ]);

        self::assertArrayHasKey(
            'user.1',
            $items,
        );

        self::assertArrayHasKey(
            'user.2',
            $items,
        );

        self::assertArrayHasKey(
            'missing',
            $items,
        );

        self::assertTrue(
            $items['user.1']->isHit(),
        );

        self::assertFalse(
            $items['missing']->isHit(),
        );
    }

    public function testDeleteItemRemovesCache(): void
    {
        $item = $this->pool->getItem(
            'user.1',
        );

        $item->set([
            'id' => 1,
        ]);

        self::assertTrue(
            $this->pool->save(
                $item,
            ),
        );

        self::assertTrue(
            $this->pool->deleteItem(
                'user.1',
            ),
        );

        self::assertFalse(
            $this->pool->hasItem(
                'user.1',
            ),
        );
    }

    public function testDeleteItemsRemovesEveryKey(): void
    {
        foreach (
            [
                'user.1',
                'user.2',
                'user.3',
            ] as $key
        ) {
            $item = $this->pool->getItem(
                $key,
            );

            $item->set([
                'key' => $key,
            ]);

            self::assertTrue(
                $this->pool->save(
                    $item,
                ),
            );
        }

        self::assertTrue(
            $this->pool->deleteItems([
                'user.1',
                'user.2',
                'user.3',
            ]),
        );

        self::assertFalse(
            $this->pool->hasItem(
                'user.1',
            ),
        );

        self::assertFalse(
            $this->pool->hasItem(
                'user.2',
            ),
        );

        self::assertFalse(
            $this->pool->hasItem(
                'user.3',
            ),
        );
    }

    public function testClearRemovesCaches(): void
    {
        foreach (
            [
                'one',
                'two',
            ] as $key
        ) {
            $item = $this->pool->getItem(
                $key,
            );

            $item->set([
                'key' => $key,
            ]);

            self::assertTrue(
                $this->pool->save(
                    $item,
                ),
            );
        }

        self::assertTrue(
            $this->pool->clear(),
        );

        self::assertFalse(
            $this->pool->hasItem(
                'one',
            ),
        );

        self::assertFalse(
            $this->pool->hasItem(
                'two',
            ),
        );
    }

    public function testSaveWithExpirationUsesTtl(): void
    {
        $item = $this->pool->getItem(
            'short',
        );

        $item
            ->set([
                'value' => 1,
            ])
            ->expiresAfter(
                1,
            );

        self::assertTrue(
            $this->pool->save(
                $item,
            ),
        );

        self::assertTrue(
            $this->pool->hasItem(
                'short',
            ),
        );

        sleep(2);

        self::assertFalse(
            $this->pool->hasItem(
                'short',
            ),
        );
    }

    public function testExpiredItemIsNotStored(): void
    {
        $item = $this->pool->getItem(
            'expired',
        );

        $item
            ->set([
                'value' => 1,
            ])
            ->expiresAfter(
                -1,
            );

        self::assertTrue(
            $this->pool->save(
                $item,
            ),
        );

        self::assertFalse(
            $this->pool->hasItem(
                'expired',
            ),
        );
    }

    public function testSaveDeferredDoesNotImmediatelyPersist(): void
    {
        $item = $this->pool->getItem(
            'deferred',
        );

        $item->set([
            'value' => 1,
        ]);

        self::assertTrue(
            $this->pool->saveDeferred(
                $item,
            ),
        );

        self::assertFalse(
            $this->pool->hasItem(
                'deferred',
            ),
        );
    }

    public function testCommitPersistsDeferredItems(): void
    {
        $first = $this->pool->getItem(
            'first',
        );

        $first->set([
            'id' => 1,
        ]);

        $second = $this->pool->getItem(
            'second',
        );

        $second->set([
            'id' => 2,
        ]);

        self::assertTrue(
            $this->pool->saveDeferred(
                $first,
            ),
        );

        self::assertTrue(
            $this->pool->saveDeferred(
                $second,
            ),
        );

        self::assertTrue(
            $this->pool->commit(),
        );

        self::assertTrue(
            $this->pool->hasItem(
                'first',
            ),
        );

        self::assertTrue(
            $this->pool->hasItem(
                'second',
            ),
        );
    }

    public function testEmptyKeyThrowsException(): void
    {
        $this->expectException(
            InvalidCacheArgumentException::class,
        );

        $this->pool->getItem('');
    }

    public function testReservedCharacterThrowsException(): void
    {
        $this->expectException(
            InvalidCacheArgumentException::class,
        );

        $this->pool->getItem(
            'user:1',
        );
    }
}
