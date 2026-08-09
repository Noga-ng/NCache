<?php declare(strict_types=1);

namespace NCache\Tests\Units\Registry;

use NCache\Exceptions\InvalidCacheArgumentException;
use NCache\Registry\CacheRegistry;
use NCache\Tests\TestsUnit\TestsUnit;

final class CacheRegistryTest extends TestsUnit
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->directory('ncache-registry-');
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->directory);

        parent::tearDown();
    }

    public function testRegistryStartsWithVersionOne(): void
    {
        $item = $this->createJsonItem('users');
        $registry = new CacheRegistry($item);

        self::assertTrue($registry->save());

        $data = $registry->getRegistry();

        self::assertSame(1, $data['version']);
        self::assertArrayHasKey('entries', $data);
    }

    public function testGetAllReturnsOnlyEntries(): void
    {
        $item = $this->createJsonItem('users');
        $registry = new CacheRegistry($item);

        self::assertTrue($registry->save());

        $entries = $registry->getAll();

        self::assertArrayHasKey(
            $item->hashedKey(),
            $entries
        );

        self::assertArrayNotHasKey(
            'version',
            $entries
        );
    }

    public function testMultipleEntriesAccumulateInsideEntries(): void
    {
        $firstItem = $this->createJsonItem('first');
        $secondItem = $this->createJsonItem('second');
        $thirdItem = $this->createJsonItem('third');

        $first = new CacheRegistry($firstItem);
        $second = new CacheRegistry($secondItem);
        $third = new CacheRegistry($thirdItem);

        self::assertTrue($first->save());
        self::assertTrue($second->save());
        self::assertTrue($third->save());

        $registry = $first->getRegistry();

        self::assertSame(1, $registry['version']);
        self::assertCount(3, $registry['entries']);

        self::assertArrayHasKey(
            $firstItem->hashedKey(),
            $registry['entries']
        );

        self::assertArrayHasKey(
            $secondItem->hashedKey(),
            $registry['entries']
        );

        self::assertArrayHasKey(
            $thirdItem->hashedKey(),
            $registry['entries']
        );
    }

    public function testSavingSameKeyReplacesOnlyItsEntry(): void
    {
        $firstItem = $this->createJsonItem('users');
        $firstItem->setSignature('v1');

        $first = new CacheRegistry($firstItem);

        self::assertTrue($first->save());

        $secondItem = $this->createJsonItem('products');
        $second = new CacheRegistry($secondItem);

        self::assertTrue($second->save());

        $updatedItem = $this->createJsonItem('users');
        $updatedItem->setSignature('v2');

        $updated = new CacheRegistry($updatedItem);

        self::assertTrue($updated->save());

        $data = $updated->getRegistry();

        self::assertSame(1, $data['version']);
        self::assertCount(2, $data['entries']);

        self::assertSame(
            $updatedItem->getSignature(),
            $data['entries'][$updatedItem->hashedKey()]['signature']
        );

        self::assertArrayHasKey(
            $secondItem->hashedKey(),
            $data['entries']
        );
    }

    public function testInvalidRegistryVersionTypeThrowsException(): void
    {
        $item = $this->createJsonItem('invalid-version');

        $invalid = [
            'version' => '1',
            'entries' => [],
        ];

        self::assertNotFalse(
            file_put_contents(
                $this->registryPath(),
                serialize($invalid)
            )
        );

        $registry = new CacheRegistry($item);

        $this->expectException(
            InvalidCacheArgumentException::class
        );

        $this->expectExceptionMessage(
            'Registry version must be an integer.'
        );

        $registry->getRegistry();
    }

    public function testUnsupportedRegistryVersionThrowsException(): void
    {
        $item = $this->createJsonItem('unsupported-version');

        $invalid = [
            'version' => 2,
            'entries' => [],
        ];

        self::assertNotFalse(
            file_put_contents(
                $this->registryPath(),
                serialize($invalid)
            )
        );

        $registry = new CacheRegistry($item);

        $this->expectException(
            InvalidCacheArgumentException::class
        );

        $this->expectExceptionMessage(
            'Unsupported registry version 2.'
        );

        $registry->getRegistry();
    }

    public function testInvalidEntriesStructureThrowsException(): void
    {
        $item = $this->createJsonItem('invalid-entries');

        $invalid = [
            'version' => 1,
            'entries' => 'invalid',
        ];

        self::assertNotFalse(
            file_put_contents(
                $this->registryPath(),
                serialize($invalid)
            )
        );

        $registry = new CacheRegistry($item);

        $this->expectException(
            InvalidCacheArgumentException::class
        );

        $registry->getRegistry();
    }

    public function testRemovePreservesRegistryVersion(): void
    {
        $firstItem = $this->createJsonItem('first');
        $secondItem = $this->createJsonItem('second');

        $first = new CacheRegistry($firstItem);
        $second = new CacheRegistry($secondItem);

        self::assertTrue($first->save());
        self::assertTrue($second->save());

        self::assertTrue($first->remove());

        $registry = $second->getRegistry();

        self::assertSame(1, $registry['version']);
        self::assertCount(1, $registry['entries']);

        self::assertArrayHasKey(
            $secondItem->hashedKey(),
            $registry['entries']
        );
    }

    public function testRemovingLastEntryDeletesRegistryFile(): void
    {
        $item = $this->createJsonItem('last-entry');

        $registry = new CacheRegistry($item);

        self::assertTrue($registry->save());
        self::assertFileExists(
            $this->registryPath()
        );

        self::assertTrue($registry->remove());

        self::assertFileDoesNotExist(
            $this->registryPath()
        );
    }

    public function testRemoveMissingRemovesOnlyMissingFilesFromCurrentDirectory(): void
    {
        $firstItem = $this->createJsonItem('first');
        $secondItem = $this->createJsonItem('second');

        $firstFile = $this->createFile('json/first.json');
        $secondFile = $this->createFile('json/second.json');

        $firstItem->setDir('json');
        $secondItem->setDir('json');

        $first = new CacheRegistry($firstItem);
        $second = new CacheRegistry($secondItem);

        $first->setFile($firstFile);
        $second->setFile($secondFile);

        self::assertTrue($first->save());
        self::assertTrue($second->save());

        self::assertTrue(unlink($firstFile));

        self::assertSame(
            1,
            $first->removeMissing()
        );

        $entries = $first->getAll();

        self::assertArrayNotHasKey(
            $firstItem->hashedKey(),
            $entries
        );

        self::assertArrayHasKey(
            $secondItem->hashedKey(),
            $entries
        );
    }

    public function testRemoveMissingDoesNotRemoveAnotherType(): void
    {
        $jsonItem = $this->createJsonItem('json-cache');
        $serializeItem = $this->createSerializeItem('serialize-cache');

        $jsonItem->setDir('shared');
        $serializeItem->setDir('shared');

        $jsonFile = $this->createFile('shared/json.json');
        $serializeFile = $this->createFile('shared/serialize.dat');

        $jsonRegistry = new CacheRegistry($jsonItem);
        $serializeRegistry = new CacheRegistry($serializeItem);

        $jsonRegistry->setFile($jsonFile);
        $serializeRegistry->setFile($serializeFile);

        self::assertTrue($jsonRegistry->save());
        self::assertTrue($serializeRegistry->save());

        self::assertTrue(unlink($jsonFile));
        self::assertTrue(unlink($serializeFile));

        self::assertSame(
            1,
            $jsonRegistry->removeMissing()
        );

        $entries = $jsonRegistry->getAll();

        self::assertArrayNotHasKey(
            $jsonItem->hashedKey(),
            $entries
        );

        self::assertArrayHasKey(
            $serializeItem->hashedKey(),
            $entries
        );
    }

    public function testRemoveMissingDoesNotRemoveSameTypeFromAnotherDirectory(): void
    {
        $firstItem = $this->createJsonItem('first');
        $secondItem = $this->createJsonItem('second');

        $firstItem->setDir('json/first');
        $secondItem->setDir('json/second');

        $firstFile = $this->createFile(
            'json/first/first.json'
        );

        $secondFile = $this->createFile(
            'json/second/second.json'
        );

        $firstRegistry = new CacheRegistry($firstItem);
        $secondRegistry = new CacheRegistry($secondItem);

        $firstRegistry->setFile($firstFile);
        $secondRegistry->setFile($secondFile);

        self::assertTrue($firstRegistry->save());
        self::assertTrue($secondRegistry->save());

        self::assertTrue(unlink($firstFile));
        self::assertTrue(unlink($secondFile));

        self::assertSame(
            1,
            $firstRegistry->removeMissing()
        );

        $entries = $firstRegistry->getAll();

        self::assertArrayNotHasKey(
            $firstItem->hashedKey(),
            $entries
        );

        self::assertArrayHasKey(
            $secondItem->hashedKey(),
            $entries
        );
    }

    public function testRemoveMissingReturnsZeroWhenAllFilesExist(): void
    {
        $item = $this->createJsonItem('existing');

        $item->setDir('json');

        $file = $this->createFile(
            'json/existing.json'
        );

        $registry = new CacheRegistry($item);
        $registry->setFile($file);

        self::assertTrue($registry->save());

        self::assertSame(
            0,
            $registry->removeMissing()
        );

        self::assertTrue($registry->has());
    }

    private function registryPath(): string
    {
        return rtrim(
            $this->directory,
            '/\\'
        )
            . DIRECTORY_SEPARATOR
            . 'NCache.nc';
    }
}
