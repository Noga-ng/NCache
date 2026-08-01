<?php
//  Cette suite comprend 17 tests. Elle vérifie notamment :

// la construction du fichier .nc ;
// l’écriture et la lecture sérialisées ;
// la conservation précise des types PHP ;
// les clés numériques, contrairement au JSON qui peut modifier certaines structures ;
// les métadonnées ;
// la suppression et le nettoyage ;
// l’écrasement du cache ;
// la création automatique du répertoire ;
// les sauvegardes successives. 

declare(strict_types=1);

namespace NCache\Tests\Units\Driver;

use NCache\Core\CacheItem\CacheItem;
use NCache\Core\CachePath;
use NCache\Driver\SerializeCache;
use NCache\Enum\CType;
use PHPUnit\Framework\TestCase;

final class SerializeCacheTest extends TestCase
{
    private string $directory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->directory = sys_get_temp_dir()
            . DIRECTORY_SEPARATOR
            . 'ncache-serialize-driver-'
            . bin2hex(random_bytes(8));

        self::assertTrue(
            mkdir($this->directory, 0777, true)
        );
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->directory);

        parent::tearDown();
    }

    public function testGetFileReturnsNcFilePath(): void
    {
        $item = $this->createItem('user-cache');
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
            $this->createItem('missing-cache')
        );

        self::assertFalse($driver->exists());
        self::assertFileDoesNotExist($driver->getFile());
    }

    public function testSaveCreatesSerializedCacheFile(): void
    {
        $item = $this->createItem('users');

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
        $item = $this->createItem('valid-serialized-data');

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
        self::assertSame($item->toArray(), $decoded);
    }

    public function testGetReturnsCompleteCacheArray(): void
    {
        $item = $this->createItem('complete-cache');

        $item->setSignature('users-v1');
        $item->setTtl(3600);
        $item->setData([
            'id' => 25,
            'name' => 'Noga',
        ]);

        $driver = new SerializeCache($item);
        $driver->save();

        self::assertSame(
            $item->toArray(),
            $driver->get()
        );
    }

    public function testShowReturnsCurrentItemWithoutReadingFile(): void
    {
        $item = $this->createItem('show-cache');

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

    public function testMetadataReturnsCacheInformationWithoutPayload(): void
    {
        $item = $this->createItem('metadata-cache');

        $item->setSignature('serialized-signature');
        $item->setTtl(120);
        $item->setData([
            'secret' => 'payload',
        ]);

        $driver = new SerializeCache($item);
        $driver->save();

        $metadata = $driver->metaData();

        self::assertArrayNotHasKey('data', $metadata);

        self::assertSame(
            [
                'type' => CType::SERIALIZE->name,
                'name' => 'metadata-cache',
                'key' => $item->hashedKey(),
                'signature' => $item->getSignature(),
                'ttl' => 120,
                'expiresAt' => $item->expiredAt(),
            ],
            $metadata
        );
    }

    public function testDeleteRemovesSavedCache(): void
    {
        $item = $this->createItem('delete-cache');

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
            $this->createItem('missing-delete')
        );

        self::assertFileDoesNotExist($driver->getFile());
        self::assertTrue($driver->delete());
    }

    public function testClearDeletesAllNcFilesInDirectory(): void
    {
        $firstItem = $this->createItem('first');
        $firstItem->setData(['id' => 1]);

        $secondItem = $this->createItem('second');
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
        $item = $this->createItem('serialized-cache');
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
        $firstItem = $this->createItem('replace-cache');

        $firstItem->setData([
            'version' => 1,
        ]);

        $firstDriver = new SerializeCache($firstItem);
        $firstDriver->save();

        $secondItem = $this->createItem('replace-cache');

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
            $result['data']
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

        $item = $this->createItem('nested-cache');
        $item->setData($data);

        $driver = new SerializeCache($item);
        $driver->save();

        $result = $driver->get();

        self::assertIsArray($result);
        self::assertSame($data, $result['data']);
    }

    public function testSerializedCachePreservesNumericArrayKeys(): void
    {
        $data = [
            10 => 'PHP',
            20 => 'JavaScript',
            50 => 'Go',
        ];

        $item = $this->createItem('numeric-keys');
        $item->setData($data);

        $driver = new SerializeCache($item);
        $driver->save();

        $result = $driver->get();

        self::assertIsArray($result);
        self::assertSame($data, $result['data']);
    }

    public function testSerializedCachePreservesUnicodeContent(): void
    {
        $data = [
            'message' => 'Données malagasy — Toamasina',
            'country' => 'Madagascar',
        ];

        $item = $this->createItem('unicode-cache');
        $item->setData($data);

        $driver = new SerializeCache($item);
        $driver->save();

        $result = $driver->get();

        self::assertIsArray($result);
        self::assertSame($data, $result['data']);
    }

    public function testCacheDirectoryIsCreatedAutomatically(): void
    {
        $nestedDirectory = $this->directory
            . DIRECTORY_SEPARATOR
            . 'nested'
            . DIRECTORY_SEPARATOR
            . 'serialize';

        $item = new CacheItem(
            'automatic-directory',
            CType::SERIALIZE,
            new CachePath($nestedDirectory)
        );

        $item->setData([
            'created' => true,
        ]);

        $driver = new SerializeCache($item);

        self::assertDirectoryDoesNotExist($nestedDirectory);

        self::assertTrue($driver->save());

        self::assertDirectoryExists($nestedDirectory);
        self::assertFileExists($driver->getFile());
    }

    public function testMultipleSuccessiveSavesKeepLatestContent(): void
    {
        $key = 'successive-cache';

        foreach ([1, 2, 3] as $version) {
            $item = $this->createItem($key);

            $item->setData([
                'version' => $version,
            ]);

            (new SerializeCache($item))->save();
        }

        $readerItem = $this->createItem($key);
        $driver = new SerializeCache($readerItem);

        $result = $driver->get();

        self::assertIsArray($result);
        $v = ['version'=>3];
        self::assertSame(
            $v,
            $result['data']
        );
    }

    private function createItem(string $key): CacheItem
    {
        return new CacheItem(
            $key,
            CType::SERIALIZE,
            new CachePath($this->directory)
        );
    }

    private function removeDirectory(string $directory): void
    {
        if (!is_dir($directory)) {
            return;
        }

        $items = scandir($directory);

        if ($items === false) {
            return;
        }

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $path = $directory
                . DIRECTORY_SEPARATOR
                . $item;

            if (is_dir($path) && !is_link($path)) {
                $this->removeDirectory($path);
                continue;
            }

            @unlink($path);
        }

        @rmdir($directory);
    }
}

