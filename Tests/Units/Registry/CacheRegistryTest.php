<?php declare(strict_types=1);

namespace NCache\Tests\Units\Registry;

use NCache\Core\CacheItem\CacheItem;
use NCache\Exceptions\InvalidCacheArgumentException;
use NCache\Registry\CacheRegistry;
use NCache\Tests\TestsUnit\TestsUnit;

final class CacheRegistryTest extends TestsUnit
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->directory(
            'ncache-registry-',
        );
    }

    protected function tearDown(): void
    {
        $this->removeDirectory(
            $this->directory,
        );

        parent::tearDown();
    }

    public function testRegistryStartsWithVersionOne(): void
    {
        $item = $this->createJsonItem(
            'users',
        );

        $registry = new CacheRegistry(
            $item,
        );

        self::assertTrue(
            $registry->save(),
        );

        $data = $registry->getRegistry();

        self::assertSame(
            1,
            $data['version'],
        );

        self::assertArrayHasKey(
            'entries',
            $data,
        );
    }

    public function testGetAllReturnsOnlyEntries(): void
    {
        $item = $this->createJsonItem(
            'users',
        );

        $registry = new CacheRegistry(
            $item,
        );

        self::assertTrue(
            $registry->save(),
        );

        $entries = $registry->getAll();

        self::assertArrayHasKey(
            $item->hashedKey(),
            $entries,
        );

        self::assertArrayNotHasKey(
            'version',
            $entries,
        );
    }

    public function testMultipleEntriesAccumulateInsideEntries(): void
    {
        $firstItem = $this->createJsonItem(
            'first',
        );

        $secondItem = $this->createJsonItem(
            'second',
        );

        $thirdItem = $this->createJsonItem(
            'third',
        );

        $first = new CacheRegistry(
            $firstItem,
        );

        $second = new CacheRegistry(
            $secondItem,
        );

        $third = new CacheRegistry(
            $thirdItem,
        );

        self::assertTrue(
            $first->save(),
        );

        self::assertTrue(
            $second->save(),
        );

        self::assertTrue(
            $third->save(),
        );

        $registry = $first->getRegistry();

        self::assertSame(
            1,
            $registry['version'],
        );

        self::assertCount(
            3,
            $registry['entries'],
        );

        self::assertArrayHasKey(
            $firstItem->hashedKey(),
            $registry['entries'],
        );

        self::assertArrayHasKey(
            $secondItem->hashedKey(),
            $registry['entries'],
        );

        self::assertArrayHasKey(
            $thirdItem->hashedKey(),
            $registry['entries'],
        );
    }

    public function testSavingSameKeyReplacesOnlyItsEntry(): void
    {
        $firstItem = $this->createJsonItem(
            'users',
        );

        $firstItem->setSignature(
            'v1',
        );

        $first = new CacheRegistry(
            $firstItem,
        );

        self::assertTrue(
            $first->save(),
        );

        $secondItem = $this->createJsonItem(
            'products',
        );

        $second = new CacheRegistry(
            $secondItem,
        );

        self::assertTrue(
            $second->save(),
        );

        $updatedItem = $this->createJsonItem(
            'users',
        );

        $updatedItem->setSignature(
            'v2',
        );

        $updated = new CacheRegistry(
            $updatedItem,
        );

        self::assertTrue(
            $updated->save(),
        );

        $data = $updated->getRegistry();

        self::assertSame(
            1,
            $data['version'],
        );

        self::assertCount(
            2,
            $data['entries'],
        );

        self::assertSame(
            $updatedItem->getSignature(),
            $data['entries'][
                $updatedItem->hashedKey()
            ]['signature'],
        );

        self::assertArrayHasKey(
            $secondItem->hashedKey(),
            $data['entries'],
        );
    }

    public function testInvalidRegistryVersionTypeThrowsException(): void
    {
        $item = $this->createJsonItem(
            'invalid-version',
        );

        $invalid = [
            'version' => '1',
            'entries' => [],
        ];

        self::assertNotFalse(
            file_put_contents(
                $this->registryPath(
                    $item,
                ),
                serialize(
                    $invalid,
                ),
            ),
        );

        $registry = new CacheRegistry(
            $item,
        );

        $this->expectException(
            InvalidCacheArgumentException::class,
        );

        $this->expectExceptionMessage(
            'Registry version must be an integer.',
        );

        $registry->getRegistry();
    }

    public function testUnsupportedRegistryVersionThrowsException(): void
    {
        $item = $this->createJsonItem(
            'unsupported-version',
        );

        $invalid = [
            'version' => 2,
            'entries' => [],
        ];

        self::assertNotFalse(
            file_put_contents(
                $this->registryPath(
                    $item,
                ),
                serialize(
                    $invalid,
                ),
            ),
        );

        $registry = new CacheRegistry(
            $item,
        );

        $this->expectException(
            InvalidCacheArgumentException::class,
        );

        $this->expectExceptionMessage(
            'Unsupported registry version 2.',
        );

        $registry->getRegistry();
    }

    public function testInvalidEntriesStructureThrowsException(): void
    {
        $item = $this->createJsonItem(
            'invalid-entries',
        );

        $invalid = [
            'version' => 1,
            'entries' => 'invalid',
        ];

        self::assertNotFalse(
            file_put_contents(
                $this->registryPath(
                    $item,
                ),
                serialize(
                    $invalid,
                ),
            ),
        );

        $registry = new CacheRegistry(
            $item,
        );

        $this->expectException(
            InvalidCacheArgumentException::class,
        );

        $registry->getRegistry();
    }

    public function testRegistryStoresTags(): void
    {
        $item = $this->createJsonItem(
            'users',
        );

        $item->setTags([
            'users',
            'api',
        ]);

        $registry = new CacheRegistry(
            $item,
        );

        self::assertTrue(
            $registry->save(),
        );

        self::assertSame(
            [
                'state' => true,
                'entries' => [
                    'users',
                    'api',
                ],
            ],
            $registry->get()['tags'],
        );
    }

    public function testRegistryWithoutTagsStoresNull(): void
    {
        $item = $this->createJsonItem(
            'users',
        );

        $registry = new CacheRegistry(
            $item,
        );

        self::assertTrue(
            $registry->save(),
        );

        self::assertNull(
            $registry->get()['tags'],
        );
    }

    public function testTagIsValidReturnsTrueForActiveTags(): void
    {
        $item = $this->createJsonItem(
            'users',
        );

        $item->setTags([
            'users',
        ]);

        $registry = new CacheRegistry(
            $item,
        );

        self::assertTrue(
            $registry->save(),
        );

        self::assertTrue(
            $registry->tagIsValid(),
        );
    }

    public function testInvalidateTagChangesStateWithoutRemovingEntry(): void
    {
        $item = $this->createJsonItem(
            'users',
        );

        $item->setTags([
            'users',
            'api',
        ]);

        $registry = new CacheRegistry(
            $item,
        );

        self::assertTrue(
            $registry->save(),
        );

        self::assertTrue(
            $registry->invalidateTag(
                'users',
            ),
        );

        $entry = $registry->get();

        self::assertNotNull(
            $entry,
        );

        self::assertSame(
            [
                'state' => false,
                'entries' => [
                    'users',
                    'api',
                ],
            ],
            $entry['tags'],
        );

        self::assertFalse(
            $registry->tagIsValid(),
        );
    }

    public function testInvalidateUnknownTagDoesNotChangeState(): void
    {
        $item = $this->createJsonItem(
            'users',
        );

        $item->setTags([
            'users',
        ]);

        $registry = new CacheRegistry(
            $item,
        );

        self::assertTrue(
            $registry->save(),
        );

        self::assertTrue(
            $registry->invalidateTag(
                'unknown',
            ),
        );

        self::assertTrue(
            $registry->tagIsValid(),
        );
    }

    public function testInvalidateTagInvalidatesEveryMatchingEntry(): void
    {
        $first = $this->createJsonItem(
            'user.1',
        );

        $first->setTags([
            'users',
        ]);

        $firstRegistry = new CacheRegistry(
            $first,
        );

        self::assertTrue(
            $firstRegistry->save(),
        );

        $second = $this->createJsonItem(
            'user.2',
        );

        $second->setTags([
            'users',
            'api',
        ]);

        $secondRegistry = new CacheRegistry(
            $second,
        );

        self::assertTrue(
            $secondRegistry->save(),
        );

        self::assertTrue(
            $firstRegistry->invalidateTag(
                'users',
            ),
        );

        self::assertFalse(
            $firstRegistry->tagIsValid(),
        );

        self::assertFalse(
            $secondRegistry->tagIsValid(),
        );
    }

    public function testRemovePreservesRegistryVersion(): void
    {
        $firstItem = $this->createJsonItem(
            'first',
        );

        $secondItem = $this->createJsonItem(
            'second',
        );

        $first = new CacheRegistry(
            $firstItem,
        );

        $second = new CacheRegistry(
            $secondItem,
        );

        self::assertTrue(
            $first->save(),
        );

        self::assertTrue(
            $second->save(),
        );

        self::assertTrue(
            $first->remove(),
        );

        $registry = $second->getRegistry();

        self::assertSame(
            1,
            $registry['version'],
        );

        self::assertCount(
            1,
            $registry['entries'],
        );

        self::assertArrayHasKey(
            $secondItem->hashedKey(),
            $registry['entries'],
        );
    }

    public function testRemovingLastEntryDeletesRegistryFile(): void
    {
        $item = $this->createJsonItem(
            'last-entry',
        );

        $registry = new CacheRegistry(
            $item,
        );

        self::assertTrue(
            $registry->save(),
        );

        self::assertFileExists(
            $this->registryPath(
                $item,
            ),
        );

        self::assertTrue(
            $registry->remove(),
        );

        self::assertFileDoesNotExist(
            $this->registryPath(
                $item,
            ),
        );
    }

    public function testRemoveMissingRemovesOnlyMissingFilesFromCurrentDirectory(): void
    {
        $firstItem = $this->createJsonItem(
            'first',
        );

        $secondItem = $this->createJsonItem(
            'second',
        );

        $firstItem->setDir(
            'json',
        );

        $secondItem->setDir(
            'json',
        );

        $firstFile = $this->createCacheFile(
            $firstItem,
            'first.json',
        );

        $secondFile = $this->createCacheFile(
            $secondItem,
            'second.json',
        );

        $first = new CacheRegistry(
            $firstItem,
        );

        $second = new CacheRegistry(
            $secondItem,
        );

        $first->setFile(
            $firstFile,
        );

        $second->setFile(
            $secondFile,
        );

        self::assertTrue(
            $first->save(),
        );

        self::assertTrue(
            $second->save(),
        );

        self::assertTrue(
            unlink(
                $firstFile,
            ),
        );

        self::assertSame(
            1,
            $first->removeMissing(),
        );

        $entries = $first->getAll();

        self::assertArrayNotHasKey(
            $firstItem->hashedKey(),
            $entries,
        );

        self::assertArrayHasKey(
            $secondItem->hashedKey(),
            $entries,
        );
    }

    public function testRemoveMissingDoesNotRemoveAnotherType(): void
    {
        $jsonItem = $this->createJsonItem(
            'json-cache',
        );

        $serializeItem =
            $this->createSerializeItem(
                'serialize-cache',
            );

        $jsonItem->setDir(
            'shared',
        );

        $serializeItem->setDir(
            'shared',
        );

        $jsonFile = $this->createCacheFile(
            $jsonItem,
            'json.json',
        );

        $serializeFile = $this->createCacheFile(
            $serializeItem,
            'serialize.dat',
        );

        $jsonRegistry = new CacheRegistry(
            $jsonItem,
        );

        $serializeRegistry = new CacheRegistry(
            $serializeItem,
        );

        $jsonRegistry->setFile(
            $jsonFile,
        );

        $serializeRegistry->setFile(
            $serializeFile,
        );

        self::assertTrue(
            $jsonRegistry->save(),
        );

        self::assertTrue(
            $serializeRegistry->save(),
        );

        self::assertTrue(
            unlink(
                $jsonFile,
            ),
        );

        self::assertTrue(
            unlink(
                $serializeFile,
            ),
        );

        self::assertSame(
            1,
            $jsonRegistry->removeMissing(),
        );

        $entries =
            $jsonRegistry->getAll();

        self::assertArrayNotHasKey(
            $jsonItem->hashedKey(),
            $entries,
        );

        self::assertArrayHasKey(
            $serializeItem->hashedKey(),
            $entries,
        );
    }

    public function testRemoveMissingDoesNotRemoveSameTypeFromAnotherDirectory(): void
    {
        $firstItem = $this->createJsonItem(
            'first',
        );

        $secondItem = $this->createJsonItem(
            'second',
        );

        $firstItem->setDir(
            'json/first',
        );

        $secondItem->setDir(
            'json/second',
        );

        $firstFile = $this->createCacheFile(
            $firstItem,
            'first.json',
        );

        $secondFile = $this->createCacheFile(
            $secondItem,
            'second.json',
        );

        $firstRegistry = new CacheRegistry(
            $firstItem,
        );

        $secondRegistry = new CacheRegistry(
            $secondItem,
        );

        $firstRegistry->setFile(
            $firstFile,
        );

        $secondRegistry->setFile(
            $secondFile,
        );

        self::assertTrue(
            $firstRegistry->save(),
        );

        self::assertTrue(
            $secondRegistry->save(),
        );

        self::assertTrue(
            unlink(
                $firstFile,
            ),
        );

        self::assertTrue(
            unlink(
                $secondFile,
            ),
        );

        self::assertSame(
            1,
            $firstRegistry->removeMissing(),
        );

        $entries =
            $firstRegistry->getAll();

        self::assertArrayNotHasKey(
            $firstItem->hashedKey(),
            $entries,
        );

        self::assertArrayHasKey(
            $secondItem->hashedKey(),
            $entries,
        );
    }

    public function testRemoveMissingReturnsZeroWhenAllFilesExist(): void
    {
        $item = $this->createJsonItem(
            'existing',
        );

        $item->setDir(
            'json',
        );

        $file = $this->createCacheFile(
            $item,
            'existing.json',
        );

        $registry = new CacheRegistry(
            $item,
        );

        $registry->setFile(
            $file,
        );

        self::assertTrue(
            $registry->save(),
        );

        self::assertSame(
            0,
            $registry->removeMissing(),
        );

        self::assertTrue(
            $registry->has(),
        );
    }

    public function testRemoveCurrentScopeRemovesOnlyMatchingTypeAndNamespace(): void
    {
        $usersFirst = $this->createSQLiteItem(
            'user-1',
        );

        $usersSecond = $this->createSQLiteItem(
            'user-2',
        );

        $admins = $this->createSQLiteItem(
            'admin-1',
        );

        $usersFirst->setDir(
            'users',
        );

        $usersSecond->setDir(
            'users',
        );

        $admins->setDir(
            'admins',
        );

        $usersFirstRegistry =
            new CacheRegistry(
                $usersFirst,
            );

        $usersSecondRegistry =
            new CacheRegistry(
                $usersSecond,
            );

        $adminsRegistry =
            new CacheRegistry(
                $admins,
            );

        self::assertTrue(
            $usersFirstRegistry->save(),
        );

        self::assertTrue(
            $usersSecondRegistry->save(),
        );

        self::assertTrue(
            $adminsRegistry->save(),
        );

        self::assertSame(
            2,
            $usersFirstRegistry
                ->removeCurrentScope(),
        );

        self::assertFalse(
            $usersFirstRegistry->has(),
        );

        self::assertFalse(
            $usersSecondRegistry->has(),
        );

        self::assertTrue(
            $adminsRegistry->has(),
        );
    }

    public function testRemoveCurrentScopeDoesNotRemoveAnotherTypeWithSameNamespace(): void
    {
        $sqlite = $this->createSQLiteItem(
            'same-key',
        );

        $redis = $this->createRedisItem(
            'same-key',
        );

        $sqlite->setDir(
            'users',
        );

        $redis->setDir(
            'users',
        );

        $sqliteRegistry =
            new CacheRegistry(
                $sqlite,
            );

        $redisRegistry =
            new CacheRegistry(
                $redis,
            );

        self::assertTrue(
            $sqliteRegistry->save(),
        );

        self::assertTrue(
            $redisRegistry->save(),
        );

        self::assertSame(
            1,
            $sqliteRegistry
                ->removeCurrentScope(),
        );

        self::assertFalse(
            $sqliteRegistry->has(),
        );

        self::assertTrue(
            $redisRegistry->has(),
        );
    }

    public function testRemoveByTypeRemovesAllNamespacesOfCurrentType(): void
    {
        $sqliteUsers =
            $this->createSQLiteItem(
                'sqlite-users',
            );

        $sqliteAdmins =
            $this->createSQLiteItem(
                'sqlite-admins',
            );

        $redis =
            $this->createRedisItem(
                'redis-users',
            );

        $json =
            $this->createJsonItem(
                'json-users',
            );

        $sqliteUsers->setDir(
            'users',
        );

        $sqliteAdmins->setDir(
            'admins',
        );

        $redis->setDir(
            'users',
        );

        $json->setDir(
            'users',
        );

        $sqliteUsersRegistry =
            new CacheRegistry(
                $sqliteUsers,
            );

        $sqliteAdminsRegistry =
            new CacheRegistry(
                $sqliteAdmins,
            );

        $redisRegistry =
            new CacheRegistry(
                $redis,
            );

        $jsonRegistry =
            new CacheRegistry(
                $json,
            );

        self::assertTrue(
            $sqliteUsersRegistry->save(),
        );

        self::assertTrue(
            $sqliteAdminsRegistry->save(),
        );

        self::assertTrue(
            $redisRegistry->save(),
        );

        self::assertTrue(
            $jsonRegistry->save(),
        );

        self::assertSame(
            2,
            $sqliteUsersRegistry
                ->removeByType(),
        );

        self::assertFalse(
            $sqliteUsersRegistry->has(),
        );

        self::assertFalse(
            $sqliteAdminsRegistry->has(),
        );

        self::assertTrue(
            $redisRegistry->has(),
        );

        self::assertTrue(
            $jsonRegistry->has(),
        );
    }

    public function testRemoveByTypeDeletesRegistryWhenNoEntryRemains(): void
    {
        $item = $this->createSQLiteItem(
            'sqlite-only',
        );

        $item->setDir(
            'users',
        );

        $registry = new CacheRegistry(
            $item,
        );

        self::assertTrue(
            $registry->save(),
        );

        self::assertSame(
            1,
            $registry->removeByType(),
        );

        self::assertSame(
            [],
            $registry->getAll(),
        );

        self::assertFileDoesNotExist(
            $this->registryPath(
                $item,
            ),
        );
    }

    private function registryPath(
        CacheItem $item,
    ): string {
        return rtrim(
            $item->basePath(),
            '/\\',
        )
            . DIRECTORY_SEPARATOR
            . 'NCache.nc';
    }

    private function createCacheFile(
        CacheItem $item,
        string $name,
        string $content = 'cache',
    ): string {
        $directory = $item->path();

        if (!is_dir($directory)) {
            self::assertTrue(
                mkdir(
                    $directory,
                    0777,
                    true,
                ),
            );
        }

        $file = $directory
            . DIRECTORY_SEPARATOR
            . $name;

        self::assertNotFalse(
            file_put_contents(
                $file,
                $content,
            ),
        );

        return $file;
    }
}
