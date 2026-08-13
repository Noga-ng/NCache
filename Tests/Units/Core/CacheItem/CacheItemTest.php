<?php

declare(strict_types=1);

namespace NCache\Tests\Units\Core\CacheItem;

use NCache\Core\Hash;
use NCache\Enum\CType;
use NCache\Tests\TestsUnit\TestsUnit;

final class CacheItemTest extends TestsUnit
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->directory(
            'ncache-item-tests-'
        );
    }

    protected function tearDown(): void
    {
        $this->removeDirectory(
            $this->directory
        );

        parent::tearDown();
    }

    public function testItReturnsTheOriginalKey(): void
    {
        $item = $this->createItem(
            'users'
        );

        self::assertSame(
            'users',
            $item->key()
        );
    }

    public function testItReturnsTheHashedKey(): void
    {
        $item = $this->createItem(
            'users'
        );

        $dir = $item->getDir()
            ?? 'default';

        $expected
            = new Hash([
                'type' => $item->typeName(),
                'dir' => $dir,
                'key' => $item->key(),
            ])
        ->get();

        self::assertSame(
            $expected,
            $item->hashedKey()
        );
    }

    public function testItReturnsTheConfiguredType(): void
    {
        $item = $this->createItem(
            'users'
        );

        self::assertSame(
            CType::JSON,
            $item->type()
        );

        self::assertSame(
            'JSON',
            $item->typeName()
        );
    }

    public function testDataIsEmptyInitially(): void
    {
        $item = $this->createItem(
            'users'
        );

        self::assertSame(
            [],
            $item->getData()
        );
    }

    public function testSetDataAddsData(): void
    {
        $item = $this->createItem(
            'users'
        );

        $item->setData([
            'id' => 1,
            'name' => 'Noga',
        ]);

        self::assertSame(
            [
                'id' => 1,
                'name' => 'Noga',
            ],
            $item->getData()
        );
    }

    public function testAppendDataAccumulatesValues(): void
    {
        $item = $this->createItem(
            'users'
        );

        $item->setData([
            [
                'id' => 1,
                'name' => 'Noga',
            ],
        ]);

        $item->appendData([
            [
                'id' => 2,
                'name' => 'Germainio',
            ],
        ]);

        self::assertSame(
            [
                [
                    'id' => 1,
                    'name' => 'Noga',
                ],
                [
                    'id' => 2,
                    'name' => 'Germainio',
                ],
            ],
            $item->getData()
        );
    }

    public function testSignatureIsNullInitially(): void
    {
        $item = $this->createItem(
            'users'
        );

        self::assertNull(
            $item->getSignature()
        );
    }

    public function testSetSignatureGeneratesExpectedHash(): void
    {
        $item = $this->createItem(
            'users'
        );

        $signatureData = [
            'updated_at' => '2026-07-28',
            'version' => 1,
        ];

        $item->setSignature(
            $signatureData
        );

        self::assertSame(
            new Hash(
                $signatureData
            )
            ->get(),
            $item->getSignature()
        );
    }

    public function testTtlIsNullInitially(): void
    {
        $item = $this->createItem(
            'users'
        );

        self::assertNull(
            $item->ttlValue()
        );

        self::assertNull(
            $item->expiredAt()
        );

        self::assertFalse(
            $item->ttlWasDefined()
        );
    }

    public function testSetTtlDefinesExpirationInformation(): void
    {
        $item = $this->createItem(
            'users'
        );

        $before = time();

        $item->setTtl(
            3600,
            $this->clock()
        );

        $after = time();

        self::assertTrue(
            $item->ttlWasDefined()
        );

        self::assertSame(
            3600,
            $item->ttlValue()
        );

        self::assertNotNull(
            $item->expiredAt()
        );

        self::assertGreaterThanOrEqual(
            $before + 3600,
            $item->expiredAt()
        );

        self::assertLessThanOrEqual(
            $after + 3600,
            $item->expiredAt()
        );
    }

    public function testSetTtlWithoutValueUsesConfiguredDefaultTtl(): void
    {
        $item = $this->createItem(
            'users'
        );

        /*
         * Ce test suppose que le profil "default"
         * de TestsUnit définit defaultTtl.
         *
         * S'il vaut null dans ta config de test,
         * remplace-le par un profil dédié avec
         * defaultTtl = "hours(1)".
         */
        $expected = $this
            ->config()
            ->getDefaultTtl();

        $item->setTtl(
            null,
            $this->clock()
        );

        self::assertTrue(
            $item->ttlWasDefined()
        );

        self::assertSame(
            $expected,
            $item->ttlValue()
        );

        if ($expected === null) {
            self::assertNull(
                $item->expiredAt()
            );

            return;
        }

        self::assertIsInt(
            $item->expiredAt()
        );
    }

    public function testSetDirChangesTheCachePath(): void
    {
        $item = $this->createItem(
            'users'
        );

        $basePath = $item->basePath();

        $item->setDir(
            'api'
        );

        self::assertSame(
            rtrim(
                $basePath,
                '/\\'
            )
                . DIRECTORY_SEPARATOR
                . 'api',
            $item->path()
        );

        self::assertSame(
            'api',
            $item->getDir()
        );
    }

    public function testFileReturnsHashedFilePath(): void
    {
        $item = $this->createItem(
            'users',
            CType::JSON
        );

        self::assertSame(
            rtrim(
                $item->path(),
                '/\\'
            )
                . DIRECTORY_SEPARATOR
                . $item->hashedKey(),
            $item->file()
        );
    }

    public function testRedisFileIsNull(): void
    {
        $item = $this->createItem(
            'users',
            CType::REDIS
        );

        self::assertNull(
            $item->file()
        );
    }

    public function testMemcachedFileIsNull(): void
    {
        $item = $this->createItem(
            'users',
            CType::MEMCACHED
        );

        self::assertNull(
            $item->file()
        );
    }

    public function testSQLiteFileReturnsCurrentCachePath(): void
    {
        $item = $this->createItem(
            'users',
            CType::SQLite
        );

        self::assertSame(
            $item->path(),
            $item->file()
        );
    }

    public function testRedisConfigurationIsAvailableForRedisItem(): void
    {
        $item = $this->createItem(
            'users',
            CType::REDIS
        );

        self::assertSame(
            $this->config()->getRedis(),
            $item->redisConfig()
        );
    }

    public function testRedisConfigurationIsNullForAnotherDriver(): void
    {
        $item = $this->createItem(
            'users',
            CType::JSON
        );

        self::assertNull(
            $item->redisConfig()
        );
    }

    public function testMemcachedConfigurationIsAvailableForMemcachedItem(): void
    {
        $item = $this->createItem(
            'users',
            CType::MEMCACHED
        );

        self::assertSame(
            $this->config()->getMemcached(),
            $item->memcachedConfig()
        );
    }

    public function testMemcachedConfigurationIsNullForAnotherDriver(): void
    {
        $item = $this->createItem(
            'users',
            CType::JSON
        );

        self::assertNull(
            $item->memcachedConfig()
        );
    }

    public function testJsonExtensionIsJson(): void
    {
        $item = $this->createItem(
            'users',
            CType::JSON
        );

        self::assertSame(
            'json',
            $item->extension()
        );
    }

    public function testSerializeExtensionComesFromConfiguration(): void
    {
        $item = $this->createItem(
            'users',
            CType::SERIALIZE
        );

        self::assertSame(
            $this
                ->config()
                ->getExtension(
                    CType::SERIALIZE
                ),
            $item->extension()
        );
    }

    public function testStringExtensionComesFromConfiguration(): void
    {
        $item = $this->createItem(
            'users',
            CType::STRING
        );

        self::assertSame(
            $this
                ->config()
                ->getExtension(
                    CType::STRING
                ),
            $item->extension()
        );
    }

    public function testToArrayReturnsTheCompleteItemStructure(): void
    {
        $item = $this->createItem(
            'users',
            CType::JSON
        );

        $item->setSignature(
            'users-version-1'
        );

        $item->setData([
            'id' => 1,
            'name' => 'Noga',
        ]);

        $item->setTtl(
            3600,
            $this->clock()
        );

        $result = $item->toArray();

        self::assertSame(
            'JSON',
            $result['type']
        );

        self::assertSame(
            'users',
            $result['name']
        );

        self::assertSame(
            $item->hashedKey(),
            $result['key']
        );

        self::assertSame(
            new Hash(
                'users-version-1'
            )
            ->get(),
            $result['signature']
        );

        self::assertSame(
            3600,
            $result['ttl']
        );

        self::assertIsInt(
            $result['expiresAt']
        );

        self::assertSame(
            [
                'id' => 1,
                'name' => 'Noga',
            ],
            $result['data']
        );
    }
}
