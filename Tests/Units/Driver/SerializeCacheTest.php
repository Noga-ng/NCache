<?php

declare(strict_types=1);

namespace NCache\Tests\Units\Driver;

use NCache\Core\CacheItem\CacheItem;
use NCache\Core\CachePath;
use NCache\Driver\SerializeCache;
use NCache\Enum\CType;
use NCache\Tests\TestsUnit\TestsUnit;

final class SerializeCacheTest extends TestsUnit
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->directory('ncache-serialize-driver-');
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->directory);
        parent::tearDown();
    }

    public function testGetFileReturnsNcFilePath(): void
    {
        $item = $this->createSerializeItem('user-cache');
        $driver = new SerializeCache($item);

        self::assertSame(
            $item->file() . '.nc',
            $driver->getFile()
        );

        self::assertStringEndsWith(
            '.nc',
            $driver->getFile()
        );
    }

    public function testExistsReturnsFalseBeforeSave(): void
    {
        $driver = new SerializeCache(
            $this->createSerializeItem('missing-cache')
        );

        self::assertFalse($driver->exists());
        self::assertFileDoesNotExist($driver->getFile());
    }

    public function testSaveCreatesSerializedCacheFile(): void
    {
        $item = $this->createSerializeItem('users');

        $item->setData([
            'id' => 12,
            'name' => 'Noga',
        ]);

        $driver = new SerializeCache($item);

        self::assertTrue($driver->save());
        self::assertFileExists($driver->getFile());
        self::assertTrue($driver->exists());
    }

    public function testSaveWritesValidSerializedData(): void
    {
        $item = $this->createSerializeItem('valid-serialized-data');

        $item->setData([
            'name' => 'Noga',
            'active' => true,
            'roles' => ['admin', 'developer'],
        ]);

        $driver = new SerializeCache($item);
        $driver->save();

        $content = file_get_contents($driver->getFile());

        self::assertNotFalse($content);

        $decoded = unserialize(
            $content,
            ['allowed_classes' => false]
        );

        self::assertIsArray($decoded);
        self::assertSame($item->getData(), $decoded);
    }

    public function testGetReturnsCompleteCacheArray(): void
    {
        $item = $this->createSerializeItem('complete-cache');

        $item->setSignature('users-v1');
        $item->setTtl(3600, $this->clock());
        $item->setData([
            'id' => 25,
            'name' => 'Noga',
        ]);

        $driver = new SerializeCache($item);
        $driver->save();

        self::assertSame(
            $item->getData(),
            $driver->get()
        );
    }

    public function testShowReturnsCurrentItemWithoutReadingFile(): void
    {
        $item = $this->createSerializeItem('show-cache');

        $item->setData([
            'framework' => 'NCache',
        ]);

        $driver = new SerializeCache($item);

        self::assertFileDoesNotExist($driver->getFile());

        self::assertSame(
            $item->toArray(),
            $driver->show()
        );
    }

    public function testDeleteRemovesSavedCache(): void
    {
        $item = $this->createSerializeItem('delete-cache');

        $item->setData([
            'value' => 1,
        ]);

        $driver = new SerializeCache($item);
        $driver->save();

        self::assertFileExists($driver->getFile());

        self::assertTrue($driver->delete());
        self::assertFileDoesNotExist($driver->getFile());
        self::assertFalse($driver->exists());
    }

    public function testDeleteMissingCacheReturnsTrue(): void
    {
        $driver = new SerializeCache(
            $this->createSerializeItem('missing-delete')
        );

        self::assertFileDoesNotExist($driver->getFile());
        self::assertTrue($driver->delete());
    }

    public function testClearDeletesAllNcFilesInDirectory(): void
    {
        $firstItem = $this->createSerializeItem('first');
        $firstItem->setData(['id' => 1]);

        $secondItem = $this->createSerializeItem('second');
        $secondItem->setData(['id' => 2]);

        $firstDriver = new SerializeCache($firstItem);
        $secondDriver = new SerializeCache($secondItem);

        $firstDriver->save();
        $secondDriver->save();

        self::assertFileExists($firstDriver->getFile());
        self::assertFileExists($secondDriver->getFile());

        self::assertSame(
            2,
            $firstDriver->clear()
        );

        self::assertFileDoesNotExist($firstDriver->getFile());
        self::assertFileDoesNotExist($secondDriver->getFile());
    }

    public function testClearDoesNotDeleteOtherFileExtensions(): void
    {
        $item = $this->createSerializeItem('serialized-cache');
        $item->setData(['value' => 1]);

        $driver = new SerializeCache($item);
        $driver->save();

        $jsonFile = $this->directory
            . DIRECTORY_SEPARATOR
            . 'keep.json';

        $textFile = $this->directory
            . DIRECTORY_SEPARATOR
            . 'keep.txt';

        self::assertNotFalse(
            file_put_contents($jsonFile, '{}')
        );

        self::assertNotFalse(
            file_put_contents($textFile, 'keep')
        );

        self::assertSame(
            1,
            $driver->clear()
        );

        self::assertFileDoesNotExist($driver->getFile());
        self::assertFileExists($jsonFile);
        self::assertFileExists($textFile);
    }

    public function testSaveReplacesExistingCacheContent(): void
    {
        $firstItem = $this->createSerializeItem('replace-cache');

        $firstItem->setData([
            'version' => 1,
        ]);

        $firstDriver = new SerializeCache($firstItem);
        $firstDriver->save();

        $secondItem = $this->createSerializeItem('replace-cache');

        $secondItem->setData([
            'version' => 2,
        ]);

        $secondDriver = new SerializeCache($secondItem);
        $secondDriver->save();

        $result = $secondDriver->get();

        self::assertIsArray($result);
        $v = ['version' => 2];
        self::assertSame(
            $v,
            $result
        );
    }

    public function testSerializedCachePreservesNestedDataTypes(): void
    {
        $data = [
            'string' => 'NCache',
            'integer' => 42,
            'float' => 19.5,
            'boolean' => true,
            'false' => false,
            'null' => null,
            'nested' => [
                'languages' => ['PHP', 'JavaScript'],
                'configuration' => [
                    'enabled' => true,
                    'limit' => 100,
                ],
            ],
        ];

        $item = $this->createSerializeItem('nested-cache');
        $item->setData($data);

        $driver = new SerializeCache($item);
        $driver->save();

        $result = $driver->get();

        self::assertIsArray($result);
        self::assertSame($data, $result);
    }

    public function testSerializedCachePreservesNumericArrayKeys(): void
    {
        $data = [
            10 => 'PHP',
            20 => 'JavaScript',
            50 => 'Go',
        ];

        $item = $this->createSerializeItem('numeric-keys');
        $item->setData($data);

        $driver = new SerializeCache($item);
        $driver->save();

        $result = $driver->get();

        self::assertIsArray($result);
        self::assertSame($data, $result);
    }

    public function testSerializedCachePreservesUnicodeContent(): void
    {
        $data = [
            'message' => 'Données malagasy — Toamasina',
            'country' => 'Madagascar',
        ];

        $item = $this->createSerializeItem('unicode-cache');
        $item->setData($data);

        $driver = new SerializeCache($item);
        $driver->save();

        $result = $driver->get();

        self::assertIsArray($result);
        self::assertSame($data, $result);
    }

    public function testCacheDirectoryIsCreatedAutomatically(): void
    {
        $item = new CacheItem(
            'automatic-directory',
            CType::JSON,
            $this->config()
        );

        $item->setDir(
            'nested/serial'
        );

        $item->setData([
            'created' => true,
        ]);

        self::assertDirectoryExists(
            $item->path()
        );

        $driver = new SerializeCache(
            $item
        );

        self::assertFileDoesNotExist(
            $driver->getFile()
        );

        self::assertTrue(
            $driver->save()
        );

        self::assertDirectoryExists(
            $item->path()
        );

        self::assertFileExists(
            $driver->getFile()
        );
    }

    public function testMultipleSuccessiveSavesKeepLatestContent(): void
    {
        $key = 'successive-cache';

        foreach ([1, 2, 3] as $version) {
            $item = $this->createSerializeItem($key);

            $item->setData([
                'version' => $version,
            ]);

            new SerializeCache($item)->save();
        }

        $readerItem = $this->createSerializeItem($key);
        $driver = new SerializeCache($readerItem);

        $result = $driver->get();

        self::assertIsArray($result);
        $v = ['version' => 3];
        self::assertSame(
            $v,
            $result
        );
    }


}
