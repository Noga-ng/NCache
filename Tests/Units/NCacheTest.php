<?php

declare(strict_types=1);

namespace NCache\Tests\Units;

use NCache\Config\CacheConfig;
use NCache\Core\Clock\Duration;
use NCache\Enum\CType;
use NCache\NCache;
use NCache\Tests\TestsUnit\TestsUnit;

final class NCacheTest extends TestsUnit
{

    public function setUp(): void
    {
     
        parent::setUp();

        $this->directory('ncache-public-api-');

        NCache::config($this->directory);
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->directory);
         CacheConfig::resetInstance();
        parent::tearDown();
    }

    public function testConfigReturnsCacheConfig(): void
    {
        $config = NCache::config($this->directory);

        self::assertInstanceOf(
            CacheConfig::class,
            $config
        );

        self::assertSame(
            $this->directory,
            $config->getBasePath()
        );
    }

    public function testKeyReturnsNCacheInstance(): void
    {
        $cache = NCache::key(
            'users',
            CType::JSON
        );

        self::assertInstanceOf(
            NCache::class,
            $cache
        );
    }

    public function testFluentMethodsReturnSameInstance(): void
    {
        $cache = NCache::key(
            'fluent-cache',
            CType::JSON
        );

        self::assertSame(
            $cache,
            $cache->dir('json')
        );

        self::assertSame(
            $cache,
            $cache->set([
                'name' => 'Noga',
            ])
        );

        self::assertSame(
            $cache,
            $cache->signature('version-1')
        );

        self::assertSame(
            $cache,
            $cache->ttl(Duration::days(1))
        );
    }

    public function testShowReturnsPreparedJsonCacheItem(): void
    {
        $data = [
            'id' => 1,
            'name' => 'Noga',
        ];

        $cache = NCache::key(
            'show-users',
            CType::JSON
        )
            ->dir('json')
            ->set($data)
            ->signature($data)
            ->ttl(Duration::days(1));

        $result = $cache->show();

        self::assertSame('JSON', $result['type']);
        self::assertSame('show-users', $result['name']);
        self::assertIsString($result['key']);
        self::assertNotSame('', $result['key']);
        self::assertIsString($result['signature']);
        self::assertSame(86_400, $result['ttl']);
        self::assertIsInt($result['expiresAt']);
        self::assertSame($data, $result['data']);
    }

    public function testJsonCacheCompleteLifecycle(): void
    {
        $data = [
            'id' => 1,
            'name' => 'Noga',
            'active' => true,
        ];

        $cache = NCache::key(
            'json-users',
            CType::JSON
        )
            ->dir('json')
            ->set($data)
            ->signature($data)
            ->ttl(Duration::hours(1));

        self::assertFalse($cache->has());

        self::assertTrue($cache->put());

        self::assertTrue($cache->has());

        $result = $cache->get();

        self::assertIsArray($result);
        self::assertSame('JSON', $result['type']);
        self::assertSame('json-users', $result['name']);
        self::assertSame($data, $result['data']);

        self::assertTrue($cache->delete());
        self::assertFalse($cache->has());
    }

    public function testSerializeCacheCompleteLifecycle(): void
    {
        $data = [
            'language' => 'PHP',
            'version' => 8.4,
            'stable' => true,
            'nullable' => null,
            'nested' => [
                'framework' => 'NCache',
            ],
        ];

        $cache = NCache::key(
            'serialize-project',
            CType::SERIALIZE
        )
            ->dir('serialize')
            ->set($data)
            ->ttl(Duration::minutes(30));

        self::assertFalse($cache->has());
        self::assertTrue($cache->put());
        self::assertTrue($cache->has());

        $result = $cache->get();

        self::assertIsArray($result);
        self::assertSame('SERIALIZE', $result['type']);
        self::assertSame($data, $result['data']);

        self::assertTrue($cache->delete());
        self::assertFalse($cache->has());
    }

    public function testStringCacheCompleteLifecycle(): void
    {
        $data = [
            'Noga',
            42,
            true,
            null,
            [
                'city' => 'Toamasina',
            ],
        ];

        $cache = NCache::key(
            'string-values',
            CType::STRING
        )
            ->dir('string')
            ->set($data)
            ->ttl(Duration::minutes(15));

        self::assertFalse($cache->has());
        self::assertTrue($cache->put());
        self::assertTrue($cache->has());

        $result = $cache->get();

        self::assertIsString($result);
      
        self::assertTrue($cache->delete());
        self::assertFalse($cache->has());
    }

    public function testHasReturnsFalseBeforePut(): void
    {
        $cache = NCache::key(
            'not-written',
            CType::JSON
        )->dir('json');

        self::assertFalse($cache->has());
    }

    public function testDeleteIsIdempotent(): void
    {
        $cache = NCache::key(
            'delete-twice',
            CType::JSON
        )
            ->dir('json')
            ->set([
                'value' => 1,
            ]);

        self::assertTrue($cache->put());
        self::assertTrue($cache->delete());
        self::assertTrue($cache->delete());
        self::assertFalse($cache->has());
    }

    public function testValidSignatureReturnsTrue(): void
    {
        $source = [
            'version' => 1,
            'updatedAt' => '2026-08-01',
        ];

        $cache = NCache::key(
            'signature-valid',
            CType::JSON
        )
            ->dir('json')
            ->signature($source)
            ->set([
                'content' => 'cached value',
            ]);

        self::assertTrue($cache->put());

        self::assertTrue(
            $cache->hasValidSignature($source)
        );
    }

    public function testDifferentSignatureReturnsFalse(): void
    {
        $cache = NCache::key(
            'signature-invalid',
            CType::JSON
        )
            ->dir('json')
            ->signature([
                'version' => 1,
            ])
            ->set([
                'content' => 'cached value',
            ]);

        self::assertTrue($cache->put());

        self::assertFalse(
            $cache->hasValidSignature([
                'version' => 2,
            ])
        );
    }

    public function testJsonCacheSupportsMixedData(): void
    {
        $data = [
            'string' => 'NCache',
            'integer' => 125,
            'float' => 15.75,
            'true' => true,
            'false' => false,
            'null' => null,
            'array' => [
                'language' => 'PHP',
            ],
        ];

        $cache = NCache::key(
            'json-mixed',
            CType::JSON
        )
            ->dir('json')
            ->set($data);

        self::assertTrue($cache->put());

        $result = $cache->get();

        self::assertIsArray($result);
        self::assertSame($data, $result['data']);
    }

    public function testSerializeCachePreservesNumericKeys(): void
    {
        $data = [
            10 => 'PHP',
            20 => 'JavaScript',
            50 => 'Go',
        ];

        $cache = NCache::key(
            'numeric-keys',
            CType::SERIALIZE
        )
            ->dir('serialize')
            ->set($data);

        self::assertTrue($cache->put());

        $result = $cache->get();

        self::assertIsArray($result);
        self::assertSame($data, $result['data']);
    }

    public function testClearDeletesJsonCachesFromSelectedDirectory(): void
    {
        $first = NCache::key(
            'json-first',
            CType::JSON
        )
            ->dir('json')
            ->set(['id' => 1]);

        $second = NCache::key(
            'json-second',
            CType::JSON
        )
            ->dir('json')
            ->set(['id' => 2]);

        self::assertTrue($first->put());
        self::assertTrue($second->put());

        self::assertTrue($first->has());
        self::assertTrue($second->has());

        self::assertSame(
            2,
            NCache::clear(CType::JSON, 'json')
        );

        self::assertFalse($first->has());
        self::assertFalse($second->has());
    }

    public function testClearDeletesSerializeCachesFromSelectedDirectory(): void
    {
        $first = NCache::key(
            'serialize-first',
            CType::SERIALIZE
        )
            ->dir('serialize')
            ->set(['id' => 1]);

        $second = NCache::key(
            'serialize-second',
            CType::SERIALIZE
        )
            ->dir('serialize')
            ->set(['id' => 2]);

        self::assertTrue($first->put());
        self::assertTrue($second->put());

        self::assertSame(
            2,
            NCache::clear(
                CType::SERIALIZE,
                'serialize'
            )
        );

        self::assertFalse($first->has());
        self::assertFalse($second->has());
    }

    public function testClearDeletesStringCachesFromSelectedDirectory(): void
    {
        $first = NCache::key(
            'string-first',
            CType::STRING
        )
            ->dir('string')
            ->set('one');

        $second = NCache::key(
            'string-second',
            CType::STRING
        )
            ->dir('string')
            ->set('two');

        self::assertTrue($first->put());
        self::assertTrue($second->put());

        self::assertSame(
            2,
            NCache::clear(
                CType::STRING,
                'string'
            )
        );

        self::assertFalse($first->has());
        self::assertFalse($second->has());
    }
}