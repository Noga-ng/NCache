<?php

declare(strict_types=1);

namespace NCache\Tests\Units\Driver;

use NCache\Core\CacheItem\CacheItem;
use NCache\Core\CachePath;
use NCache\Driver\StringCache;
use NCache\Enum\CType;
use PHPUnit\Framework\TestCase;

final class StringCacheTest extends TestCase
{
    private string $directory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->directory = sys_get_temp_dir()
            . DIRECTORY_SEPARATOR
            . 'ncache-string-driver-'
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

    public function testGetFileReturnsTxtFilePath(): void
    {
        $driver = new StringCache(
            $this->createItem('string-cache')
        );

        self::assertStringEndsWith(
            '.txt',
            $driver->getFile()
        );
    }

    public function testExistsReturnsFalseBeforeSave(): void
    {
        $driver = new StringCache(
            $this->createItem('missing-cache')
        );

        self::assertFalse($driver->exists());
        self::assertFileDoesNotExist($driver->getFile());
    }

    public function testSaveCreatesStringCacheFile(): void
    {
        $item = $this->createItem('users');

        $item->setData([
            'Noga',
            'Germainio',
        ]);

        $driver = new StringCache($item);

        self::assertTrue($driver->save());
        self::assertFileExists($driver->getFile());
        self::assertTrue($driver->exists());
    }

    public function testGetReturnsSavedStringContent(): void
    {
        $item = $this->createItem('read-cache');

        $item->setData([
            'Noga',
            'Germainio',
        ]);

        $driver = new StringCache($item);
        $driver->save();

        $content = $driver->get();

        self::assertIsString($content);
        self::assertStringContainsString(
            'content :',
            $content
        );
        self::assertStringContainsString(
            'Noga,Germainio;',
            $content
        );
    }

    public function testSavedContentContainsMetadata(): void
    {
        $item = $this->createItem('metadata-cache');

        $item->setSignature('version-1');
        $item->setTtl(3600);
        $item->setData(['NCache']);

        $driver = new StringCache($item);
        $driver->save();

        $content = $driver->get();

        self::assertStringContainsString(
            'type : STRING;',
            $content
        );

        self::assertStringContainsString(
            'name : metadata-cache;',
            $content
        );

        self::assertStringContainsString(
            'signature : ' . $item->getSignature() . ';',
            $content
        );

        self::assertStringContainsString(
            'ttl : 3600;',
            $content
        );

        self::assertStringContainsString(
            'expiresAt : ' . $item->expiredAt() . ';',
            $content
        );
    }

    public function testItConvertsScalarValuesToStrings(): void
    {
        $item = $this->createItem('scalar-cache');

        $item->setData([
            'Noga',
            42,
            19.5,
            true,
            false,
            null,
        ]);

        $driver = new StringCache($item);
        $driver->save();

        self::assertStringContainsString(
            'Noga,42,19.5,true,false,null;',
            $driver->get()
        );
    }

    public function testItEncodesArraysAsJson(): void
    {
        $item = $this->createItem('array-cache');

        $item->setData([
            [
                'id' => 1,
                'name' => 'Noga',
            ],
        ]);

        $driver = new StringCache($item);
        $driver->save();

        self::assertStringContainsString(
            '{"id":1,"name":"Noga"}',
            $driver->get()
        );
    }

    public function testItEncodesObjectsAsJson(): void
    {
        $item = $this->createItem('object-cache');

        $item->setData([
            (object) [
                'id' => 1,
                'name' => 'Noga',
            ],
        ]);

        $driver = new StringCache($item);
        $driver->save();

        self::assertStringContainsString(
            '{"id":1,"name":"Noga"}',
            $driver->get()
        );
    }

    public function testItPreservesUnicodeContent(): void
    {
        $item = $this->createItem('unicode-cache');

        $item->setData([
            'Données malagasy',
            'Toamasina',
        ]);

        $driver = new StringCache($item);
        $driver->save();

        self::assertStringContainsString(
            'Données malagasy,Toamasina;',
            $driver->get()
        );
    }

    public function testShowReturnsCacheItemStructure(): void
    {
        $item = $this->createItem('show-cache');

        $item->setData([
            'NCache',
        ]);

        $driver = new StringCache($item);

        self::assertSame(
            $item->toArray(),
            $driver->show()
        );
    }

    public function testMetadataReturnsStoredStringInsideArray(): void
    {
        $item = $this->createItem('metadata');

        $item->setData([
            'NCache',
        ]);

        $driver = new StringCache($item);
        $driver->save();

        self::assertSame(
            [$driver->get()],
            $driver->metaData()
        );
    }

    public function testSaveReplacesExistingContent(): void
    {
        $firstItem = $this->createItem('replace-cache');
        $firstItem->setData(['version-one']);

        (new StringCache($firstItem))->save();

        $secondItem = $this->createItem('replace-cache');
        $secondItem->setData(['version-two']);

        $driver = new StringCache($secondItem);
        $driver->save();

        $content = $driver->get();

        self::assertStringContainsString(
            'version-two;',
            $content
        );

        self::assertStringNotContainsString(
            'version-one;',
            $content
        );
    }

    public function testDeleteRemovesSavedFile(): void
    {
        $item = $this->createItem('delete-cache');
        $item->setData(['value']);

        $driver = new StringCache($item);
        $driver->save();

        self::assertFileExists($driver->getFile());

        self::assertTrue($driver->delete());
        self::assertFileDoesNotExist($driver->getFile());
        self::assertFalse($driver->exists());
    }

    public function testDeleteMissingFileReturnsTrue(): void
    {
        $driver = new StringCache(
            $this->createItem('missing-delete')
        );

        self::assertFileDoesNotExist($driver->getFile());
        self::assertTrue($driver->delete());
    }

    public function testClearDeletesAllTxtCaches(): void
    {
        $firstItem = $this->createItem('first');
        $firstItem->setData(['one']);

        $secondItem = $this->createItem('second');
        $secondItem->setData(['two']);

        $firstDriver = new StringCache($firstItem);
        $secondDriver = new StringCache($secondItem);

        $firstDriver->save();
        $secondDriver->save();

        self::assertSame(
            2,
            $firstDriver->clear()
        );

        self::assertFileDoesNotExist(
            $firstDriver->getFile()
        );

        self::assertFileDoesNotExist(
            $secondDriver->getFile()
        );
    }

    public function testClearDoesNotDeleteOtherExtensions(): void
    {
        $item = $this->createItem('string-cache');
        $item->setData(['value']);

        $driver = new StringCache($item);
        $driver->save();

        $jsonFile = $this->directory
            . DIRECTORY_SEPARATOR
            . 'keep.json';

        self::assertNotFalse(
            file_put_contents($jsonFile, '{}')
        );

        self::assertSame(1, $driver->clear());

        self::assertFileDoesNotExist(
            $driver->getFile()
        );

        self::assertFileExists($jsonFile);
    }

    private function createItem(string $key): CacheItem
    {
        return new CacheItem(
            $key,
            CType::STRING,
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