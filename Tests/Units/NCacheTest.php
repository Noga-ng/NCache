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
    protected function setUp(): void
    {
        parent::setUp();

        $this->directory(
            'ncache-public-api-',
        );

        NCache::config(
            $this->configFile,
        )->use('default');
    }

    protected function tearDown(): void
    {
        $this->removeDirectory(
            $this->directory,
        );

        CacheConfig::resetInstance();

        parent::tearDown();
    }

    public function testConfigReturnsCacheConfig(): void
    {
        $config = NCache::config(
            $this->configFile,
        );

        self::assertInstanceOf(
            CacheConfig::class,
            $config,
        );

        $config = $this->config();

        self::assertSame(
            $config->state()->getBasePath(),
            $config->state()->getBasePath(),
        );
    }

    public function testKeyReturnsNCacheInstance(): void
    {
        $cache = NCache::key(
            'users',
            CType::JSON,
        );

        self::assertInstanceOf(
            NCache::class,
            $cache,
        );
    }

    public function testFluentMethodsReturnSameInstance(): void
    {
        $cache = NCache::key(
            'fluent-cache',
            CType::JSON,
        );

        self::assertSame(
            $cache,
            $cache->dir('json'),
        );

        self::assertSame(
            $cache,
            $cache->set([
                'name' => 'Noga',
            ]),
        );

        self::assertSame(
            $cache,
            $cache->signature(
                'version-1',
            ),
        );

        self::assertSame(
            $cache,
            $cache->ttl(
                Duration::days(1),
            ),
        );
    }

    public function testKeyUsesDefaultDriverWhenTypeIsOmitted(): void
    {
        $cache = NCache::key(
            'default-driver',
        );

        self::assertSame(
            'JSON',
            $cache->show()['type'],
        );
    }

    public function testExplicitDriverOverridesDefaultDriver(): void
    {
        $cache = NCache::key(
            'explicit-driver',
            CType::SERIALIZE,
        );

        self::assertSame(
            'SERIALIZE',
            $cache->show()['type'],
        );
    }

    public function testCacheIsForeverWhenTtlIsNotCalled(): void
    {
        $cache = NCache::key(
            'forever',
        )->set([
            'id' => 1,
        ]);

        self::assertTrue(
            $cache->put(),
        );

        $registry = $cache->getRegistry();

        self::assertNotNull($registry);
        self::assertNull($registry['ttl']);
        self::assertNull($registry['expiresAt']);
    }

    public function testTtlWithoutArgumentUsesDefaultTtl(): void
    {
        $cache = NCache::key(
            'default-ttl',
        )
            ->ttl()
            ->set([
                'id' => 1,
            ]);

        self::assertTrue(
            $cache->put(),
        );

        $registry = $cache->getRegistry();

        self::assertNotNull($registry);

        self::assertSame(
            3600,
            $registry['ttl'],
        );

        self::assertIsInt(
            $registry['expiresAt'],
        );
    }

    public function testExplicitTtlOverridesDefaultTtl(): void
    {
        $cache = NCache::key(
            'explicit-ttl',
        )
            ->ttl(120)
            ->set([
                'id' => 1,
            ]);

        self::assertTrue(
            $cache->put(),
        );

        $registry = $cache->getRegistry();

        self::assertNotNull($registry);

        self::assertSame(
            120,
            $registry['ttl'],
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
            CType::JSON,
        )
            ->dir('json')
            ->set($data)
            ->signature($data)
            ->ttl(
                Duration::days(1),
            );

        $result = $cache->show();

        self::assertSame(
            'JSON',
            $result['type'],
        );

        self::assertSame(
            'show-users',
            $result['name'],
        );

        self::assertIsString(
            $result['key'],
        );

        self::assertNotSame(
            '',
            $result['key'],
        );

        self::assertIsString(
            $result['signature'],
        );

        self::assertSame(
            86_400,
            $result['ttl'],
        );

        self::assertIsInt(
            $result['expiresAt'],
        );

        self::assertSame(
            $data,
            $result['data'],
        );
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
            CType::JSON,
        )
            ->dir('json')
            ->set($data)
            ->signature($data)
            ->ttl(
                Duration::hours(1),
            );

        self::assertFalse(
            $cache->has(),
        );

        self::assertTrue(
            $cache->put(),
        );

        self::assertTrue(
            $cache->has(),
        );

        $registry = $cache->getRegistry();

        self::assertNotNull(
            $registry,
        );

        self::assertSame(
            'JSON',
            $registry['type'],
        );

        self::assertSame(
            'json-users',
            $registry['name'],
        );

        self::assertSame(
            $data,
            $cache->get(),
        );

        self::assertTrue(
            $cache->delete(),
        );

        self::assertFalse(
            $cache->has(),
        );
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
            CType::SERIALIZE,
        )
            ->dir('serialize')
            ->set($data)
            ->ttl(
                Duration::minutes(30),
            );

        self::assertFalse(
            $cache->has(),
        );

        self::assertTrue(
            $cache->put(),
        );

        self::assertTrue(
            $cache->has(),
        );

        self::assertSame(
            $data,
            $cache->get(),
        );

        self::assertTrue(
            $cache->delete(),
        );

        self::assertFalse(
            $cache->has(),
        );
    }

    public function testStringCacheCompleteLifecycle(): void
    {
        $cache = NCache::key(
            'string-values',
            CType::STRING,
        )
            ->dir('string')
            ->set([
                'Noga',
                42,
                true,
                null,
                [
                    'city' => 'Toamasina',
                ],
            ])
            ->ttl(
                Duration::minutes(15),
            );

        self::assertFalse(
            $cache->has(),
        );

        self::assertTrue(
            $cache->put(),
        );

        self::assertTrue(
            $cache->has(),
        );

        self::assertIsString(
            $cache->get(),
        );

        self::assertTrue(
            $cache->delete(),
        );

        self::assertFalse(
            $cache->has(),
        );
    }

    public function testSQLiteCacheCompleteLifecycle(): void
    {
        $data = [
            'name' => 'NCache',
            'version' => 1,
        ];

        $cache = NCache::key(
            'sqlite-lifecycle',
            CType::SQLite,
        )
            ->dir('sqlite/users')
            ->set($data);

        self::assertFalse(
            $cache->has(),
        );

        self::assertTrue(
            $cache->put(),
        );

        self::assertTrue(
            $cache->has(),
        );

        self::assertSame(
            $data,
            $cache->get(),
        );

        self::assertTrue(
            $cache->delete(),
        );

        self::assertFalse(
            $cache->has(),
        );

        self::assertNull(
            $cache->get(),
        );
    }

    public function testSQLiteSameKeyInDifferentDirectoriesIsIsolated(): void
    {
        $users = NCache::key(
            'same-key',
            CType::SQLite,
        )
            ->dir('users')
            ->set([
                'source' => 'users',
            ]);

        $admins = NCache::key(
            'same-key',
            CType::SQLite,
        )
            ->dir('admins')
            ->set([
                'source' => 'admins',
            ]);

        self::assertTrue(
            $users->put(),
        );

        self::assertTrue(
            $admins->put(),
        );

        self::assertSame(
            ['source' => 'users'],
            $users->get(),
        );

        self::assertSame(
            ['source' => 'admins'],
            $admins->get(),
        );
    }

    public function testSQLitePutPreservesExpirationWhenTtlDoesNotChange(): void
    {
        $cache = NCache::key(
            'sqlite-preserve-ttl',
            CType::SQLite,
        )
            ->dir('ttl')
            ->ttl(60)
            ->set([
                'version' => 1,
            ]);

        self::assertTrue(
            $cache->put(),
        );

        $first = $cache->getRegistry();

        self::assertNotNull(
            $first,
        );

        $expiresAt = $first['expiresAt'];

        $cache->set([
            'version' => 2,
        ]);

        self::assertTrue(
            $cache->put(),
        );

        $second = $cache->getRegistry();

        self::assertNotNull(
            $second,
        );

        self::assertSame(
            $expiresAt,
            $second['expiresAt'],
        );

        self::assertSame(
            ['version' => 2],
            $cache->get(),
        );
    }

    public function testSQLiteChangingTtlCreatesNewExpiration(): void
    {
        $cache = NCache::key(
            'sqlite-change-ttl',
            CType::SQLite,
        )
            ->dir('ttl')
            ->ttl(60)
            ->set([
                'version' => 1,
            ]);

        self::assertTrue(
            $cache->put(),
        );

        $first = $cache->getRegistry();

        self::assertNotNull(
            $first,
        );

        $cache
            ->ttl(120)
            ->set([
                'version' => 2,
            ]);

        self::assertTrue(
            $cache->put(),
        );

        $second = $cache->getRegistry();

        self::assertNotNull(
            $second,
        );

        self::assertNotSame(
            $first['expiresAt'],
            $second['expiresAt'],
        );

        self::assertSame(
            120,
            $second['ttl'],
        );
    }

    public function testClearDeletesSQLiteCachesFromSelectedDirectory(): void
    {
        $first = NCache::key(
            'sqlite-a',
            CType::SQLite,
        )
            ->dir('users')
            ->set([
                'id' => 1,
            ]);

        $second = NCache::key(
            'sqlite-b',
            CType::SQLite,
        )
            ->dir('users')
            ->set([
                'id' => 2,
            ]);

        $third = NCache::key(
            'sqlite-admin',
            CType::SQLite,
        )
            ->dir('admins')
            ->set([
                'id' => 3,
            ]);

        self::assertTrue(
            $first->put(),
        );

        self::assertTrue(
            $second->put(),
        );

        self::assertTrue(
            $third->put(),
        );

        self::assertSame(
            2,
            NCache::clear(
                CType::SQLite,
                'users',
            ),
        );

        self::assertFalse(
            $first->has(),
        );

        self::assertFalse(
            $second->has(),
        );

        // self::assertTrue(
        //     $third->has()
        // );
    }

    public function testClearAllDeletesSQLiteCaches(): void
    {
        $first = NCache::key(
            'sqlite-first',
            CType::SQLite,
        )
            ->dir('users')
            ->set(['id' => 1]);

        $second = NCache::key(
            'sqlite-second',
            CType::SQLite,
        )
            ->dir('products')
            ->set(['id' => 2]);

        self::assertTrue(
            $first->put(),
        );

        self::assertTrue(
            $second->put(),
        );

        self::assertSame(
            2,
            NCache::clear(
                CType::SQLite,
            ),
        );

        self::assertFalse(
            $first->has(),
        );

        self::assertFalse(
            $second->has(),
        );
    }

    public function testHasReturnsFalseBeforePut(): void
    {
        $cache = NCache::key(
            'not-written',
            CType::JSON,
        )
            ->dir('json');

        self::assertFalse(
            $cache->has(),
        );
    }

    public function testDeleteIsIdempotent(): void
    {
        $cache = NCache::key(
            'delete-twice',
            CType::JSON,
        )
            ->dir('json')
            ->set([
                'value' => 1,
            ]);

        self::assertTrue(
            $cache->put(),
        );

        self::assertTrue(
            $cache->delete(),
        );

        self::assertTrue(
            $cache->delete(),
        );

        self::assertFalse(
            $cache->has(),
        );
    }

    public function testValidSignatureReturnsTrue(): void
    {
        $source = [
            'version' => 1,
            'updatedAt' => '2026-08-01',
        ];

        $cache = NCache::key(
            'signature-valid',
            CType::JSON,
        )
            ->dir('json')
            ->signature($source)
            ->set([
                'content' => 'cached value',
            ]);

        self::assertTrue(
            $cache->put(),
        );

        self::assertTrue(
            $cache->hasValidSignature(
                $source,
            ),
        );
    }

    public function testDifferentSignatureReturnsFalse(): void
    {
        $cache = NCache::key(
            'signature-invalid',
            CType::JSON,
        )
            ->dir('json')
            ->signature([
                'version' => 1,
            ])
            ->set([
                'content' => 'cached value',
            ]);

        self::assertTrue(
            $cache->put(),
        );

        self::assertFalse(
            $cache->hasValidSignature([
                'version' => 2,
            ]),
        );
    }

    public function testCacheCanBeTagged(): void
    {
        $cache = NCache::key(
            'users',
        )
            ->tags([
                'users',
                'api',
            ])
            ->set([
                'id' => 1,
            ]);

        self::assertTrue(
            $cache->put(),
        );

        self::assertSame(
            [
                'state' => true,
                'entries' => [
                    'users',
                    'api',
                ],
            ],
            $cache->getTags(),
        );
    }

    public function testSingleTagIsNormalized(): void
    {
        $cache = NCache::key(
            'users',
        )
            ->tags('users')
            ->set([
                'id' => 1,
            ]);

        self::assertTrue(
            $cache->put(),
        );

        self::assertSame(
            [
                'state' => true,
                'entries' => [
                    'users',
                ],
            ],
            $cache->getTags(),
        );
    }

    public function testInvalidatedTagUsesLazyDeleteOnGet(): void
    {
        $cache = NCache::key(
            'user.1',
        )
            ->tags('users')
            ->set([
                'id' => 1,
            ]);

        self::assertTrue(
            $cache->put(),
        );

        self::assertSame(
            [
                'id' => 1,
            ],
            $cache->get(),
        );

        self::assertTrue(
            NCache::invalidateTag(
                'users',
            ),
        );

        // Invalidation does not immediately remove
        // the registry entry.
        $registry = $cache->getRegistry();

        self::assertNotNull(
            $registry,
        );

        self::assertFalse(
            $registry['tags']['state'],
        );

        // Access triggers the lazy deletion.
        self::assertNull(
            $cache->get(),
        );

        // Registry entry is now removed too.
        self::assertNull(
            $cache->getRegistry(),
        );
    }

    public function testInvalidatedTagUsesLazyDeleteOnHas(): void
    {
        $cache = NCache::key(
            'user.1',
        )
            ->tags('users')
            ->set([
                'id' => 1,
            ]);

        self::assertTrue(
            $cache->put(),
        );

        self::assertTrue(
            $cache->has(),
        );

        self::assertTrue(
            NCache::invalidateTag(
                'users',
            ),
        );

        self::assertNotNull(
            $cache->getRegistry(),
        );

        self::assertFalse(
            $cache->has(),
        );

        self::assertNull(
            $cache->getRegistry(),
        );
    }

    public function testInvalidateTagDoesNotAffectUnrelatedCache(): void
    {
        $users = NCache::key(
            'user.1',
        )
            ->tags('users')
            ->set([
                'id' => 1,
            ]);

        $articles = NCache::key(
            'article.1',
        )
            ->tags('articles')
            ->set([
                'id' => 1,
            ]);

        self::assertTrue($users->put());
        self::assertTrue($articles->put());

        self::assertTrue(
            NCache::invalidateTag(
                'users',
            ),
        );

        self::assertNull(
            $users->get(),
        );

        self::assertSame(
            [
                'id' => 1,
            ],
            $articles->get(),
        );

        self::assertTrue(
            $articles->has(),
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
            CType::JSON,
        )
            ->dir('json')
            ->set($data);

        self::assertTrue(
            $cache->put(),
        );

        self::assertSame(
            $data,
            $cache->get(),
        );
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
            CType::SERIALIZE,
        )
            ->dir('serialize')
            ->set($data);

        self::assertTrue(
            $cache->put(),
        );

        self::assertSame(
            $data,
            $cache->get(),
        );
    }

    public function testClearDeletesJsonCachesFromSelectedDirectory(): void
    {
        $first = NCache::key(
            'json-first',
            CType::JSON,
        )
            ->dir('json')
            ->set(['id' => 1]);

        $second = NCache::key(
            'json-second',
            CType::JSON,
        )
            ->dir('json')
            ->set(['id' => 2]);

        self::assertTrue(
            $first->put(),
        );

        self::assertTrue(
            $second->put(),
        );

        self::assertSame(
            2,
            NCache::clear(
                CType::JSON,
                'json',
            ),
        );

        self::assertFalse(
            $first->has(),
        );

        self::assertFalse(
            $second->has(),
        );
    }

    public function testClearDeletesSerializeCachesFromSelectedDirectory(): void
    {
        $first = NCache::key(
            'serialize-first',
            CType::SERIALIZE,
        )
            ->dir('serialize')
            ->set(['id' => 1]);

        $second = NCache::key(
            'serialize-second',
            CType::SERIALIZE,
        )
            ->dir('serialize')
            ->set(['id' => 2]);

        self::assertTrue(
            $first->put(),
        );

        self::assertTrue(
            $second->put(),
        );

        self::assertSame(
            2,
            NCache::clear(
                CType::SERIALIZE,
                'serialize',
            ),
        );

        self::assertFalse(
            $first->has(),
        );

        self::assertFalse(
            $second->has(),
        );
    }

    public function testClearDeletesStringCachesFromSelectedDirectory(): void
    {
        $first = NCache::key(
            'string-first',
            CType::STRING,
        )
            ->dir('string')
            ->set('one');

        $second = NCache::key(
            'string-second',
            CType::STRING,
        )
            ->dir('string')
            ->set('two');

        self::assertTrue(
            $first->put(),
        );

        self::assertTrue(
            $second->put(),
        );

        self::assertSame(
            2,
            NCache::clear(
                CType::STRING,
                'string',
            ),
        );

        self::assertFalse(
            $first->has(),
        );

        self::assertFalse(
            $second->has(),
        );
    }

    public function testClearOnlyRemovesSelectedTypeAndDirectory(): void
    {
        $json = NCache::key(
            'same-name',
            CType::JSON,
        )
            ->dir('shared')
            ->set([
                'type' => 'json',
            ]);

        $serialize = NCache::key(
            'same-name',
            CType::SERIALIZE,
        )
            ->dir('shared')
            ->set([
                'type' => 'serialize',
            ]);

        self::assertTrue(
            $json->put(),
        );

        self::assertTrue(
            $serialize->put(),
        );

        self::assertSame(
            1,
            NCache::clear(
                CType::JSON,
                'shared',
            ),
        );

        self::assertFalse(
            $json->has(),
        );

        self::assertTrue(
            $serialize->has(),
        );
    }

    public function testClearOnlyRemovesSelectedDirectory(): void
    {
        $first = NCache::key(
            'first',
            CType::JSON,
        )
            ->dir('json/a')
            ->set(['id' => 1]);

        $second = NCache::key(
            'second',
            CType::JSON,
        )
            ->dir('json/b')
            ->set(['id' => 2]);

        self::assertTrue(
            $first->put(),
        );

        self::assertTrue(
            $second->put(),
        );

        self::assertSame(
            1,
            NCache::clear(
                CType::JSON,
                'json/a',
            ),
        );

        self::assertFalse(
            $first->has(),
        );

        self::assertTrue(
            $second->has(),
        );
    }

    public function testSQLiteClearKeepsAnotherNamespace(): void
    {
        $usersFirst = NCache::key(
            'user-1',
            CType::SQLite,
        )
            ->dir('users')
            ->set(['id' => 1]);

        $usersSecond = NCache::key(
            'user-2',
            CType::SQLite,
        )
            ->dir('users')
            ->set(['id' => 2]);

        $admins = NCache::key(
            'admin-1',
            CType::SQLite,
        )
            ->dir('admins')
            ->set(['id' => 3]);

        self::assertTrue(
            $usersFirst->put(),
        );

        self::assertTrue(
            $usersSecond->put(),
        );

        self::assertTrue(
            $admins->put(),
        );

        self::assertSame(
            2,
            NCache::clear(
                CType::SQLite,
                'users',
            ),
        );

        self::assertFalse(
            $usersFirst->has(),
        );

        self::assertFalse(
            $usersSecond->has(),
        );

        self::assertTrue(
            $admins->has(),
        );

        self::assertSame(
            ['id' => 3],
            $admins->get(),
        );
    }

    public function testSQLiteClearWithoutDirectoryRemovesAllSQLiteNamespaces(): void
    {
        $users = NCache::key(
            'sqlite-users',
            CType::SQLite,
        )
            ->dir('users')
            ->set(['id' => 1]);

        $admins = NCache::key(
            'sqlite-admins',
            CType::SQLite,
        )
            ->dir('admins')
            ->set(['id' => 2]);

        $json = NCache::key(
            'json-users',
            CType::JSON,
        )
            ->dir('users')
            ->set(['id' => 3]);

        self::assertTrue($users->put());
        self::assertTrue($admins->put());
        self::assertTrue($json->put());

        self::assertSame(
            2,
            NCache::clear(
                CType::SQLite,
            ),
        );

        self::assertFalse(
            $users->has(),
        );

        self::assertFalse(
            $admins->has(),
        );

        self::assertTrue(
            $json->has(),
        );
    }
}
