<?php

// Cette suite contient 16 tests et couvre :

// getFile();
// exists();
// save();
// get();
// show() hérité de CacheDriver;
// metaData();
// delete();
// clear();
// l’écrasement d’un cache existant;
// les données imbriquées;
// la création automatique du dossier.

declare(strict_types=1);

namespace NCache\Tests\Units\Driver;

use NCache\Core\CacheItem\CacheItem;
use NCache\Core\CachePath;
use NCache\Driver\JsonCache;
use NCache\Enum\CType;
use PHPUnit\Framework\TestCase;

final class JsonCacheTest extends TestCase
{
    private string $directory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->directory = sys_get_temp_dir()
            . DIRECTORY_SEPARATOR
            . 'ncache-json-driver-'
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

    public function testGetFileReturnsJsonFilePath(): void
    {
        $item = $this->createItem('user-cache');
        $driver = new JsonCache($item);

        self::assertSame(
            $item->file() . '.json',
            $driver->getFile()
        );
    }

    public function testGetFileDoesNotDuplicateJsonExtension(): void
    {
        $item = $this->createItem('cache');

        $expectedFile = $item->file() . '.json';

        self::assertStringEndsWith(
            '.json',
            (new JsonCache($item))->getFile()
        );

        self::assertSame(
            $expectedFile,
            (new JsonCache($item))->getFile()
        );
    }

    public function testExistsReturnsFalseBeforeSave(): void
    {
        $driver = new JsonCache(
            $this->createItem('missing-cache')
        );

        self::assertFalse($driver->exists());
    }

    public function testSaveCreatesJsonFile(): void
    {
        $item = $this->createItem('users');
        $item->setData([
            'id' => 12,
            'name' => 'Noga',
        ]);

        $driver = new JsonCache($item);

        self::assertTrue($driver->save());
        self::assertFileExists($driver->getFile());
        self::assertTrue($driver->exists());
    }

    public function testSaveWritesValidJson(): void
    {
        $item = $this->createItem('valid-json');

        $item->setData([
            'name' => 'Noga',
            'active' => true,
            'roles' => ['admin', 'developer'],
        ]);

        $driver = new JsonCache($item);
        $driver->save();

        $content = file_get_contents($driver->getFile());

        self::assertNotFalse($content);

        $decoded = json_decode(
            $content,
            true,
            512,
            JSON_THROW_ON_ERROR
        );

        self::assertIsArray($decoded);
        self::assertSame($item->toArray(), $decoded);
    }

    public function testSaveUsesPrettyPrintedJson(): void
{
    $item = $this->createItem('pretty-json');

    $item->setData([
        'name' => 'Noga',
    ]);

    $driver = new JsonCache($item);
    $driver->save();

    $content = file_get_contents($driver->getFile());

    self::assertNotFalse($content);
    self::assertStringContainsString("\n", $content);

    $compactJson = json_encode(
        $item->toArray(),
        JSON_THROW_ON_ERROR
    );

    self::assertNotSame($compactJson, $content);
}

    public function testGetReturnsCompleteCacheArray(): void
    {
        $item = $this->createItem('cache-data');

        $item->setSignature('users-v1');
        $item->setTtl(3600);
        $item->setData([
            'id' => 25,
            'name' => 'Noga',
        ]);

        $driver = new JsonCache($item);
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

        $driver = new JsonCache($item);

        self::assertFileDoesNotExist($driver->getFile());

        self::assertSame(
            $item->toArray(),
            $driver->show()
        );
    }

    public function testMetadataReturnsDataWithoutCachePayload(): void
    {
        $item = $this->createItem('metadata-cache');

        $item->setSignature('signature');
        $item->setTtl(120);
        $item->setData([
            'secret' => 'payload',
        ]);

        $driver = new JsonCache($item);
        $driver->save();

        $metadata = $driver->metaData();

        self::assertArrayNotHasKey('data', $metadata);

        self::assertSame(
            [
                'type' => CType::JSON->name,
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
        $item->setData(['value' => 1]);

        $driver = new JsonCache($item);
        $driver->save();

        self::assertFileExists($driver->getFile());

        self::assertTrue($driver->delete());
        self::assertFileDoesNotExist($driver->getFile());
        self::assertFalse($driver->exists());
    }

    public function testDeleteMissingCacheReturnsTrue(): void
    {
        $driver = new JsonCache(
            $this->createItem('missing-delete')
        );

        self::assertFileDoesNotExist($driver->getFile());
        self::assertTrue($driver->delete());
    }

    public function testClearDeletesAllJsonCachesInDirectory(): void
    {
        $first = $this->createItem('first');
        $first->setData(['id' => 1]);

        $second = $this->createItem('second');
        $second->setData(['id' => 2]);

        $firstDriver = new JsonCache($first);
        $secondDriver = new JsonCache($second);

        $firstDriver->save();
        $secondDriver->save();

        self::assertFileExists($firstDriver->getFile());
        self::assertFileExists($secondDriver->getFile());

        self::assertSame(2, $firstDriver->clear());

        self::assertFileDoesNotExist($firstDriver->getFile());
        self::assertFileDoesNotExist($secondDriver->getFile());
    }

    public function testClearDoesNotDeleteOtherExtensions(): void
    {
        $item = $this->createItem('json-cache');
        $item->setData(['value' => 1]);

        $driver = new JsonCache($item);
        $driver->save();

        $textFile = $this->directory
            . DIRECTORY_SEPARATOR
            . 'keep.txt';

        file_put_contents($textFile, 'keep');

        self::assertSame(1, $driver->clear());

        self::assertFileDoesNotExist($driver->getFile());
        self::assertFileExists($textFile);
    }

    public function testSaveReplacesExistingCacheContent(): void
    {
        $firstItem = $this->createItem('replace-cache');
        $firstItem->setData([
            'version' => 1,
        ]);

        $firstDriver = new JsonCache($firstItem);
        $firstDriver->save();

        $secondItem = $this->createItem('replace-cache');
        $secondItem->setData([
            'version' => 2,
        ]);

        $secondDriver = new JsonCache($secondItem);
        $secondDriver->save();

        $result = $secondDriver->get();

        self::assertSame(
            ['version' => 2],
            $result['data']
        );
    }

    public function testJsonPreservesNestedDataTypes(): void
    {
        $item = $this->createItem('nested-cache');

        $item->setData([
            'string' => 'NCache',
            'integer' => 42,
            'float' => 19.5,
            'boolean' => true,
            'null' => null,
            'nested' => [
                'languages' => ['PHP', 'JavaScript'],
            ],
        ]);

        $driver = new JsonCache($item);
        $driver->save();

        $result = $driver->get();

        self::assertSame(
            $item->getData(),
            $result['data']
        );
    }

    public function testCacheItemDirectoryIsCreatedAutomatically(): void
    {
        $nestedDirectory = $this->directory
            . DIRECTORY_SEPARATOR
            . 'nested'
            . DIRECTORY_SEPARATOR
            . 'json';

        $item = new CacheItem(
            'automatic-directory',
            CType::JSON,
            new CachePath($nestedDirectory)
        );

        $item->setData([
            'created' => true,
        ]);

        $driver = new JsonCache($item);

        self::assertDirectoryDoesNotExist($nestedDirectory);

        self::assertTrue($driver->save());

        self::assertDirectoryExists($nestedDirectory);
        self::assertFileExists($driver->getFile());
    }

    private function createItem(string $key): CacheItem
    {
        return new CacheItem(
            $key,
            CType::JSON,
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