<?php declare(strict_types=1);

namespace NCache\Tests\Units\Registry;

use NCache\Enum\CType;
use NCache\Registry\CacheRegistry;
use NCache\TestsUnit\TestsUnit;
use UnexpectedValueException;

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

    public function testGetValuesReturnsEmptyArrayWhenRegistryDoesNotExist(): void
    {
        $registry = new CacheRegistry(
            $this->createItem('missing-registry')
        );

        self::assertSame([], $registry->getAll());
    }

    public function testGetReturnsNullWhenEntryDoesNotExist(): void
    {
        $registry = new CacheRegistry(
            $this->createItem('missing-entry')
        );

        self::assertNull($registry->get());
    }

    public function testHasReturnsFalseWhenEntryDoesNotExist(): void
    {
        $registry = new CacheRegistry(
            $this->createItem('missing-entry')
        );

        self::assertFalse($registry->has());
    }

    public function testSaveCreatesRegistryFile(): void
    {
        $item = $this->createItem('users');
        $registry = new CacheRegistry($item);

        self::assertTrue($registry->save());

        self::assertFileExists(
            $this->registryPath()
        );
    }

    public function testSaveRegistersCurrentCacheItem(): void
    {
        $item = $this->createItem(
            'users',
            CType::JSON
        );

        $item->setSignature([
            'version' => 1,
        ]);

        $item->setTtl(3600, $this->clock());

        $registry = new CacheRegistry($item);

        $registry->setFile($item->file());

        self::assertTrue($registry->save());

        $entry = $registry->get();

        self::assertNotNull($entry);
        self::assertSame('JSON', $entry['type']);
        self::assertSame(
            $item->hashedKey(),
            $entry['key']
        );
        self::assertSame(
            $item->key(),
            $entry['name']
        );

        self::assertSame(
            $item->file(),
            $entry['file']
        );

        self::assertSame(
            $item->getSignature(),
            $entry['signature']
        );
        self::assertSame(3600, $entry['ttl']);
        self::assertSame(
            $item->expiredAt(),
            $entry['expiresAt']
        );
    }

    public function testRegistryDoesNotContainCacheData(): void
    {
        $item = $this->createItem('large-data');

        $item->setData([
            'users' => [
                ['id' => 1, 'name' => 'Noga'],
                ['id' => 2, 'name' => 'Germainio'],
            ],
        ]);

        $registry = new CacheRegistry($item);

        self::assertTrue($registry->save());

        $entry = $registry->get();

        self::assertNotNull($entry);
        self::assertArrayNotHasKey('data', $entry);
    }

    public function testHasReturnsTrueAfterSave(): void
    {
        $registry = new CacheRegistry(
            $this->createItem('existing-entry')
        );

        self::assertFalse($registry->has());
        self::assertTrue($registry->save());
        self::assertTrue($registry->has());
    }

    public function testMultipleEntriesArePreserved(): void
    {
        $firstItem = $this->createItem(
            'first',
            CType::JSON
        );

        $secondItem = $this->createItem(
            'second',
            CType::SERIALIZE
        );

        $firstRegistry = new CacheRegistry($firstItem);
        $secondRegistry = new CacheRegistry($secondItem);

        self::assertTrue($firstRegistry->save());
        self::assertTrue($secondRegistry->save());

        $values = $firstRegistry->getAll();

        self::assertCount(2, $values);

        self::assertArrayHasKey(
            $firstItem->hashedKey(),
            $values
        );

        self::assertArrayHasKey(
            $secondItem->hashedKey(),
            $values
        );
    }

    public function testSavingSameKeyReplacesExistingEntry(): void
    {
        $firstItem = $this->createItem(
            'replace-entry',
            CType::JSON
        );

        $firstItem->setSignature('version-one');
        $firstItem->setTtl(60, $this->clock());

        $firstRegistry = new CacheRegistry($firstItem);

        self::assertTrue($firstRegistry->save());

        $secondItem = $this->createItem(
            'replace-entry',
            CType::JSON
        );

        $secondItem->setSignature('version-two');
        $secondItem->setTtl(3600, $this->clock());

        $secondRegistry = new CacheRegistry($secondItem);

        self::assertTrue($secondRegistry->save());

        $values = $secondRegistry->getAll();

        self::assertCount(1, $values);

        $entry = $secondRegistry->get();

        self::assertNotNull($entry);

        self::assertSame(
            $secondItem->getSignature(),
            $entry['signature']
        );

        self::assertSame(3600, $entry['ttl']);
    }

    public function testRemoveDeletesCurrentEntry(): void
    {
        $item = $this->createItem('remove-entry');
        $registry = new CacheRegistry($item);

        self::assertTrue($registry->save());
        self::assertTrue($registry->has());

        self::assertTrue($registry->remove());

        self::assertFalse($registry->has());
        self::assertNull($registry->get());
    }

    public function testRemovePreservesOtherEntries(): void
    {
        $firstItem = $this->createItem('first');
        $secondItem = $this->createItem('second');

        $firstRegistry = new CacheRegistry($firstItem);
        $secondRegistry = new CacheRegistry($secondItem);

        self::assertTrue($firstRegistry->save());
        self::assertTrue($secondRegistry->save());

        self::assertTrue($firstRegistry->remove());

        self::assertFalse($firstRegistry->has());
        self::assertTrue($secondRegistry->has());

        self::assertCount(
            1,
            $secondRegistry->getAll()
        );
    }

    public function testRemovingLastEntryDeletesRegistryFile(): void
    {
        $registry = new CacheRegistry(
            $this->createItem('last-entry')
        );

        self::assertTrue($registry->save());
        self::assertFileExists($this->registryPath());

        self::assertTrue($registry->remove());

        self::assertFileDoesNotExist(
            $this->registryPath()
        );
    }

    public function testRemoveMissingEntryIsIdempotent(): void
    {
        $registry = new CacheRegistry(
            $this->createItem('missing-remove')
        );

        self::assertTrue($registry->remove());
        self::assertTrue($registry->remove());
    }

    public function testClearReturnsNumberOfRemovedEntries(): void
    {
        $firstRegistry = new CacheRegistry(
            $this->createItem('first')
        );

        $secondRegistry = new CacheRegistry(
            $this->createItem('second')
        );

        self::assertTrue($firstRegistry->save());
        self::assertTrue($secondRegistry->save());

        self::assertSame(
            2,
            $firstRegistry->clear()
        );

        self::assertFileDoesNotExist(
            $this->registryPath()
        );
    }

    public function testClearReturnsZeroWhenRegistryDoesNotExist(): void
    {
        $registry = new CacheRegistry(
            $this->createItem('empty-clear')
        );

        self::assertSame(0, $registry->clear());
    }

    public function testRegistryFileContainsSerializedArray(): void
    {
        $item = $this->createItem('serialized-registry');
        $registry = new CacheRegistry($item);

        self::assertTrue($registry->save());

        $content = file_get_contents(
            $this->registryPath()
        );

        self::assertNotFalse($content);

        $decoded = unserialize(
            $content,
            ['allowed_classes' => false]
        );

        self::assertIsArray($decoded);

        self::assertArrayHasKey(
            $item->hashedKey(),
            $decoded
        );
    }

    public function testInvalidRegistryEntryTypeThrowsException(): void
    {
        $item = $this->createItem('invalid-type');

        $invalidRegistry = [
            $item->hashedKey() => [
                'type' => 123,
                'name' => $item->key(),
                'key' => $item->hashedKey(),
                'file' => $item->file(),
                'signature' => null,
                'ttl' => null,
                'expiresAt' => null,
            ],
        ];

        self::assertNotFalse(
            file_put_contents(
                $this->registryPath(),
                serialize($invalidRegistry)
            )
        );

        $registry = new CacheRegistry($item);

        $this->expectException(
            UnexpectedValueException::class
        );

        $this->expectExceptionMessage(
            'Registry entry type must be a string.'
        );

        $registry->getAll();
    }

    public function testInvalidTtlTypeThrowsException(): void
    {
        $item = $this->createItem('invalid-ttl');

        $invalidRegistry = [
            $item->hashedKey() => [
                'type' => 'JSON',
                'name' => $item->key(),
                'key' => $item->hashedKey(),
                'file' => $item->file(),
                'signature' => null,
                'ttl' => '3600',
                'expiresAt' => null,
            ],
        ];

        self::assertNotFalse(
            file_put_contents(
                $this->registryPath(),
                serialize($invalidRegistry)
            )
        );

        $registry = new CacheRegistry($item);

        $this->expectException(
            UnexpectedValueException::class
        );

        $this->expectExceptionMessage(
            'ttl must be an integer or null.'
        );

        $registry->getAll();
    }

    public function testInvalidExpiresAtTypeThrowsException(): void
    {
        $item = $this->createItem(
            'invalid-expiration'
        );

        $invalidRegistry = [
            $item->hashedKey() => [
                'type' => 'JSON',
                'name' => $item->key(),
                'key' => $item->hashedKey(),
                'file' => $item->file(),
                'signature' => null,
                'ttl' => 3600,
                'expiresAt' => 'tomorrow',
            ],
        ];

        self::assertNotFalse(
            file_put_contents(
                $this->registryPath(),
                serialize($invalidRegistry)
            )
        );

        $registry = new CacheRegistry($item);

        $this->expectException(
            UnexpectedValueException::class
        );

        $this->expectExceptionMessage(
            'expiresAt must be an integer or null.'
        );

        $registry->getAll();
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
