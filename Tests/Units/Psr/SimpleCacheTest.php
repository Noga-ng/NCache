<?php

declare(strict_types=1);

namespace NCache\Tests\Units\Psr;

use DateInterval;
use NCache\Enum\CType;
use NCache\Psr\SimpleCache\Exceptions\InvalidCacheArgumentException;
use NCache\Psr\SimpleCache\SimpleCache;
use NCache\Tests\TestsUnit\TestsUnit;

final class SimpleCacheTest extends TestsUnit
{
    private SimpleCache $cache;

    protected function setUp(): void
    {
        parent::setUp();

        $this->directory(
            'ncache-psr16-',
        );

        $this->cache = new SimpleCache(
            $this->configFile,
            'default',
            CType::JSON,
        );
    }

    public function testSetAndGetValue(): void
    {
        self::assertTrue(
            $this->cache->set(
                'user.1',
                [
                    'id' => 1,
                    'name' => 'Noga',
                ],
            ),
        );

        self::assertSame(
            [
                'id' => 1,
                'name' => 'Noga',
            ],
            $this->cache->get(
                'user.1',
            ),
        );
    }

    public function testGetReturnsDefaultWhenKeyDoesNotExist(): void
    {
        self::assertSame(
            'fallback',
            $this->cache->get(
                'missing',
                'fallback',
            ),
        );
    }

    public function testHasReturnsTrueForExistingCache(): void
    {
        self::assertTrue(
            $this->cache->set(
                'user.1',
                ['id' => 1],
            ),
        );

        self::assertTrue(
            $this->cache->has(
                'user.1',
            ),
        );
    }

    public function testHasReturnsFalseForMissingCache(): void
    {
        self::assertFalse(
            $this->cache->has(
                'missing',
            ),
        );
    }

    public function testDeleteRemovesCache(): void
    {
        self::assertTrue(
            $this->cache->set(
                'user.1',
                ['id' => 1],
            ),
        );

        self::assertTrue(
            $this->cache->delete(
                'user.1',
            ),
        );

        self::assertFalse(
            $this->cache->has(
                'user.1',
            ),
        );
    }

    public function testDeleteMissingKeyReturnsTrue(): void
    {
        self::assertTrue(
            $this->cache->delete(
                'missing',
            ),
        );
    }

    public function testIntegerTtlExpiresCache(): void
    {
        self::assertTrue(
            $this->cache->set(
                'short',
                ['value' => 1],
                1,
            ),
        );

        self::assertTrue(
            $this->cache->has(
                'short',
            ),
        );

        sleep(2);

        self::assertFalse(
            $this->cache->has(
                'short',
            ),
        );
    }

    public function testDateIntervalTtlIsSupported(): void
    {
        self::assertTrue(
            $this->cache->set(
                'interval',
                ['value' => 1],
                new DateInterval('PT60S'),
            ),
        );

        self::assertTrue(
            $this->cache->has(
                'interval',
            ),
        );
    }

    public function testZeroTtlDeletesCacheImmediately(): void
    {
        self::assertTrue(
            $this->cache->set(
                'zero',
                ['value' => 1],
            ),
        );

        self::assertTrue(
            $this->cache->set(
                'zero',
                ['value' => 2],
                0,
            ),
        );

        self::assertFalse(
            $this->cache->has(
                'zero',
            ),
        );
    }

    public function testNegativeTtlDeletesCacheImmediately(): void
    {
        self::assertTrue(
            $this->cache->set(
                'negative',
                ['value' => 1],
            ),
        );

        self::assertTrue(
            $this->cache->set(
                'negative',
                ['value' => 2],
                -1,
            ),
        );

        self::assertFalse(
            $this->cache->has(
                'negative',
            ),
        );
    }

    public function testGetMultipleReturnsValues(): void
    {
        self::assertTrue(
            $this->cache->set(
                'user.1',
                ['id' => 1],
            ),
        );

        self::assertTrue(
            $this->cache->set(
                'user.2',
                ['id' => 2],
            ),
        );

        self::assertSame(
            [
                'user.1' => ['id' => 1],
                'user.2' => ['id' => 2],
                'missing' => null,
            ],
            $this->cache->getMultiple([
                'user.1',
                'user.2',
                'missing',
            ]),
        );
    }

    public function testGetMultipleUsesDefaultForMissingKeys(): void
    {
        self::assertSame(
            [
                'missing.1' => 'fallback',
                'missing.2' => 'fallback',
            ],
            $this->cache->getMultiple(
                [
                    'missing.1',
                    'missing.2',
                ],
                'fallback',
            ),
        );
    }

    public function testSetMultipleStoresEveryValue(): void
    {
        self::assertTrue(
            $this->cache->setMultiple([
                'user.1' => ['id' => 1],
                'user.2' => ['id' => 2],
            ]),
        );

        self::assertSame(
            ['id' => 1],
            $this->cache->get(
                'user.1',
            ),
        );

        self::assertSame(
            ['id' => 2],
            $this->cache->get(
                'user.2',
            ),
        );
    }

    public function testSetMultipleAppliesTtl(): void
    {
        self::assertTrue(
            $this->cache->setMultiple(
                [
                    'short.1' => ['id' => 1],
                    'short.2' => ['id' => 2],
                ],
                1,
            ),
        );

        sleep(2);

        self::assertFalse(
            $this->cache->has(
                'short.1',
            ),
        );

        self::assertFalse(
            $this->cache->has(
                'short.2',
            ),
        );
    }

    public function testDeleteMultipleRemovesEveryKey(): void
    {
        self::assertTrue(
            $this->cache->setMultiple([
                'user.1' => ['id' => 1],
                'user.2' => ['id' => 2],
            ]),
        );

        self::assertTrue(
            $this->cache->deleteMultiple([
                'user.1',
                'user.2',
            ]),
        );

        self::assertFalse(
            $this->cache->has(
                'user.1',
            ),
        );

        self::assertFalse(
            $this->cache->has(
                'user.2',
            ),
        );
    }

    public function testClearRemovesCurrentDriverCaches(): void
    {
        self::assertTrue(
            $this->cache->set(
                'one',
                ['id' => 1],
            ),
        );

        self::assertTrue(
            $this->cache->set(
                'two',
                ['id' => 2],
            ),
        );

        self::assertTrue(
            $this->cache->clear(),
        );

        self::assertFalse(
            $this->cache->has(
                'one',
            ),
        );

        self::assertFalse(
            $this->cache->has(
                'two',
            ),
        );
    }

    public function testEmptyKeyThrowsInvalidArgumentException(): void
    {
        $this->expectException(
            InvalidCacheArgumentException::class,
        );

        $this->cache->get('');
    }

    public function testReservedCharactersThrowInvalidArgumentException(): void
    {
        $this->expectException(
            InvalidCacheArgumentException::class,
        );

        $this->cache->get(
            'user:1',
        );
    }

    public function testUnsupportedValueThrowsInvalidArgumentException(): void
    {
        $this->expectException(
            InvalidCacheArgumentException::class,
        );

        $this->cache->set(
            'resource',
            fopen(
                'php://memory',
                'rb',
            ),
        );
    }

    public function testTwoAdaptersKeepTheirOwnProfiles(): void
    {
        $default = new SimpleCache(
            $this->configFile,
            'default',
            CType::JSON,
        );

        $users = new SimpleCache(
            $this->configFile,
            'users',
            CType::JSON,
        );

        self::assertTrue(
            $default->set(
                'same-key',
                ['profile' => 'default'],
            ),
        );

        self::assertTrue(
            $users->set(
                'same-key',
                ['profile' => 'users'],
            ),
        );

        self::assertSame(
            ['profile' => 'default'],
            $default->get(
                'same-key',
            ),
        );

        self::assertSame(
            ['profile' => 'users'],
            $users->get(
                'same-key',
            ),
        );
    }
}
