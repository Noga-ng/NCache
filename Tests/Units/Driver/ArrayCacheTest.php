<?php

declare(strict_types=1);

namespace NCache\Tests\Units\Driver;

use NCache\Driver\ArrayCache;
use NCache\Enum\CType;
use NCache\Tests\TestsUnit\TestsUnit;

final class ArrayCacheTest extends TestsUnit
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->directory(
            'ncache-array-driver-',
        );
    }

    protected function tearDown(): void
    {
        $this->removeDirectory(
            $this->directory,
        );

        parent::tearDown();
    }

    public function testGetFileReturnsPhpFilePath(): void
    {
        $item = $this->createItem(
            'users',
            CType::ARRAY_PHP,
        );

        $driver = new ArrayCache(
            $item,
        );

        self::assertSame(
            $item->file() . '.php',
            $driver->getFile(),
        );
    }

    public function testExistsReturnsFalseBeforeSave(): void
    {
        $item = $this->createItem(
            'missing',
            CType::ARRAY_PHP,
        );

        $driver = new ArrayCache(
            $item,
        );

        self::assertFalse(
            $driver->exists(),
        );
    }

    public function testSaveCreatesPhpCacheFile(): void
    {
        $item = $this->createItem(
            'users',
            CType::ARRAY_PHP,
        );

        $item->setData([
            'id' => 1,
            'name' => 'Noga',
        ]);

        $driver = new ArrayCache(
            $item,
        );

        self::assertTrue(
            $driver->save(),
        );

        self::assertFileExists(
            $driver->getFile(),
        );

        self::assertTrue(
            $driver->exists(),
        );
    }

    public function testSavedFileContainsPhpReturnStatement(): void
    {
        $item = $this->createItem(
            'php-content',
            CType::ARRAY_PHP,
        );

        $item->setData([
            'framework' => 'NCache',
        ]);

        $driver = new ArrayCache(
            $item,
        );

        self::assertTrue(
            $driver->save(),
        );

        $content = file_get_contents(
            $driver->getFile(),
        );

        self::assertNotFalse(
            $content,
        );

        self::assertStringStartsWith(
            '<?php',
            $content,
        );

        self::assertStringContainsString(
            'return',
            $content,
        );
    }

    public function testGetReturnsSavedArray(): void
    {
        $item = $this->createItem(
            'users',
            CType::ARRAY_PHP,
        );

        $item->setData([
            'id' => 25,
            'name' => 'Noga',
        ]);

        $driver = new ArrayCache(
            $item,
        );

        self::assertTrue(
            $driver->save(),
        );

        self::assertSame(
            $item->getData(),
            $driver->get(),
        );
    }

    public function testArrayPhpPreservesNestedDataTypes(): void
    {
        $item = $this->createItem(
            'nested',
            CType::ARRAY_PHP,
        );

        $item->setData([
            'string' => 'NCache',
            'integer' => 42,
            'float' => 19.5,
            'boolean' => true,
            'null' => null,
            'nested' => [
                'languages' => [
                    'PHP',
                    'JavaScript',
                ],
            ],
        ]);

        $driver = new ArrayCache(
            $item,
        );

        self::assertTrue(
            $driver->save(),
        );

        self::assertSame(
            $item->getData(),
            $driver->get(),
        );
    }

    public function testSaveReplacesExistingCacheContent(): void
    {
        $firstItem = $this->createItem(
            'replace',
            CType::ARRAY_PHP,
        );

        $firstItem->setData([
            'version' => 1,
        ]);

        $firstDriver = new ArrayCache(
            $firstItem,
        );

        self::assertTrue(
            $firstDriver->save(),
        );

        self::assertSame(
            [
                'version' => 1,
            ],
            $firstDriver->get(),
        );

        $secondItem = $this->createItem(
            'replace',
            CType::ARRAY_PHP,
        );

        $secondItem->setData([
            'version' => 2,
        ]);

        $secondDriver = new ArrayCache(
            $secondItem,
        );

        self::assertTrue(
            $secondDriver->save(),
        );

        self::assertSame(
            [
                'version' => 2,
            ],
            $secondDriver->get(),
        );
    }

    public function testLargeArrayCanBeSavedAndRead(): void
    {
        $data = [];

        for ($i = 0; $i < 5000; $i++) {
            $data[] = [
                'id' => $i,
                'name' => "user-{$i}",
                'active' => ($i % 2) === 0,
            ];
        }

        $item = $this->createItem(
            'large',
            CType::ARRAY_PHP,
        );

        $item->setData(
            $data,
        );

        $driver = new ArrayCache(
            $item,
        );

        self::assertTrue(
            $driver->save(),
        );

        self::assertSame(
            $data,
            $driver->get(),
        );
    }

    public function testDeleteRemovesSavedCache(): void
    {
        $item = $this->createItem(
            'delete',
            CType::ARRAY_PHP,
        );

        $item->setData([
            'value' => 1,
        ]);

        $driver = new ArrayCache(
            $item,
        );

        self::assertTrue(
            $driver->save(),
        );

        self::assertFileExists(
            $driver->getFile(),
        );

        self::assertTrue(
            $driver->delete(),
        );

        self::assertFileDoesNotExist(
            $driver->getFile(),
        );

        self::assertFalse(
            $driver->exists(),
        );
    }

    public function testDeleteMissingCacheReturnsTrue(): void
    {
        $item = $this->createItem(
            'missing-delete',
            CType::ARRAY_PHP,
        );

        $driver = new ArrayCache(
            $item,
        );

        self::assertFileDoesNotExist(
            $driver->getFile(),
        );

        self::assertTrue(
            $driver->delete(),
        );
    }

    public function testClearDeletesAllPhpCachesInDirectory(): void
    {
        $first = $this->createItem(
            'first',
            CType::ARRAY_PHP,
        );

        $first->setData([
            'id' => 1,
        ]);

        $second = $this->createItem(
            'second',
            CType::ARRAY_PHP,
        );

        $second->setData([
            'id' => 2,
        ]);

        $firstDriver = new ArrayCache(
            $first,
        );

        $secondDriver = new ArrayCache(
            $second,
        );

        self::assertTrue(
            $firstDriver->save(),
        );

        self::assertTrue(
            $secondDriver->save(),
        );

        self::assertFileExists(
            $firstDriver->getFile(),
        );

        self::assertFileExists(
            $secondDriver->getFile(),
        );

        self::assertSame(
            2,
            $firstDriver->clear(),
        );

        self::assertFileDoesNotExist(
            $firstDriver->getFile(),
        );

        self::assertFileDoesNotExist(
            $secondDriver->getFile(),
        );
    }

    public function testClearDoesNotDeleteOtherExtensions(): void
    {
        $item = $this->createItem(
            'array-cache',
            CType::ARRAY_PHP,
        );

        $item->setData([
            'value' => 1,
        ]);

        $driver = new ArrayCache(
            $item,
        );

        self::assertTrue(
            $driver->save(),
        );

        $textFile = $item->path()
            . DIRECTORY_SEPARATOR
            . 'keep.txt';

        file_put_contents(
            $textFile,
            'keep',
        );

        self::assertSame(
            1,
            $driver->clear(),
        );

        self::assertFileDoesNotExist(
            $driver->getFile(),
        );

        self::assertFileExists(
            $textFile,
        );
    }
}
