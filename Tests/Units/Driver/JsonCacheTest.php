<?php
declare(strict_types=1);

namespace NCache\Tests\Units\Driver;

use NCache\Core\CacheItem\CacheItem;
use NCache\Core\CachePath;
use NCache\Driver\JsonCache;
use NCache\Enum\CType;
use NCache\Tests\TestsUnit\TestsUnit;

final class JsonCacheTest extends TestsUnit
{

    protected function setUp(): void
    {
        parent::setUp();
       $this->directory('ncache-json-driver');
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->directory);
        parent::tearDown();
    }


    public function testGetFileReturnsJsonFilePath(): void
    {
        $item = $this->createJsonItem('user-cache');
        $driver = new JsonCache($item);

        self::assertSame(
            $item->file() . '.json',
            $driver->getFile()
        );
    }

    public function testGetFileDoesNotDuplicateJsonExtension(): void
    {
        $item = $this->createJsonItem('cache');

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
            $this->createJsonItem('missing-cache')
        );

        self::assertFalse($driver->exists());
    }

    public function testSaveCreatesJsonFile(): void
    {
        $item = $this->createJsonItem('users');
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
        $item = $this->createJsonItem('valid-json');

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
        self::assertSame($item->getData(), $decoded);
    }

    public function testSaveUsesPrettyPrintedJson(): void
{
    $item = $this->createJsonItem('pretty-json');

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
        $item = $this->createJsonItem('cache-data');

        $item->setSignature('users-v1');
        $item->setTtl(3600,$this->clock());
        $item->setData([
            'id' => 25,
            'name' => 'Noga',
        ]);

        $driver = new JsonCache($item);
        $driver->save();

        self::assertSame(
            $item->getData(),
            $driver->get()
        );
    }

    public function testShowReturnsCurrentItemWithoutReadingFile(): void
    {
        $item = $this->createJsonItem('show-cache');

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


    public function testDeleteRemovesSavedCache(): void
    {
        $item = $this->createJsonItem('delete-cache');
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
            $this->createJsonItem('missing-delete')
        );

        self::assertFileDoesNotExist($driver->getFile());
        self::assertTrue($driver->delete());
    }

    public function testClearDeletesAllJsonCachesInDirectory(): void
    {
        $first = $this->createJsonItem('first');
        $first->setData(['id' => 1]);

        $second = $this->createJsonItem('second');
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
        $item = $this->createJsonItem('json-cache');
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
        $firstItem = $this->createJsonItem('replace-cache');
        $firstItem->setData([
            'version' => 1,
        ]);

        $firstDriver = new JsonCache($firstItem);
        $firstDriver->save();

        $secondItem = $this->createJsonItem('replace-cache');
        $secondItem->setData([
            'version' => 2,
        ]);

        $secondDriver = new JsonCache($secondItem);
        $secondDriver->save();

        $result = $secondDriver->get();

        self::assertSame(
            ['version' => 2],
            $result
        );
    }

    public function testJsonPreservesNestedDataTypes(): void
    {
        $item = $this->createJsonItem('nested-cache');

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
            $result
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

   

}