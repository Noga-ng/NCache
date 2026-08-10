<?php

declare(strict_types=1);

namespace NCache\Tests\Units\Driver;


use NCache\Driver\StringCache;
use NCache\Tests\TestsUnit\TestsUnit;

final class StringCacheTest extends TestsUnit
{

    protected function setUp(): void
    {
        parent::setUp();
       $this->directory('ncache-string-driver-');
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->directory);
        parent::tearDown();
    }

    public function testGetFileReturnsTxtFilePath(): void
    {
        $driver = new StringCache(
            $this->createStringItem('string-cache')
        );

        self::assertStringEndsWith(
            '.txt',
            $driver->getFile()
        );
    }

    public function testExistsReturnsFalseBeforeSave(): void
    {
        $driver = new StringCache(
            $this->createStringItem('missing-cache')
        );

        self::assertFalse($driver->exists());
        self::assertFileDoesNotExist($driver->getFile());
    }

    public function testSaveCreatesStringCacheFile(): void
    {
        $item = $this->createStringItem('users');

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
        $item = $this->createStringItem('read-cache');

        $item->setData([
            'Noga',
            'Germainio',
        ]);

        $driver = new StringCache($item);
        $driver->save();

        $content = $driver->get();

        self::assertIsString($content);

        self::assertStringContainsString(
            'Noga,Germainio',
            str_replace(PHP_EOL,',',$content)
        );
    }


    public function testItConvertsScalarValuesToStrings(): void
    {
        $item = $this->createStringItem('scalar-cache');

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
            "Noga,42,19.5,true,false,null",
            str_replace(PHP_EOL,',',$driver->get())
        );
    }

    public function testItEncodesArraysAsJson(): void
    {
        $item = $this->createStringItem('array-cache');

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
        $item = $this->createStringItem('object-cache');

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
        $item = $this->createStringItem('unicode-cache');

        $item->setData([
            'Données malagasy',
            'Toamasina',
        ]);

        $driver = new StringCache($item);
        $driver->save();

        self::assertStringContainsString(
            'Données malagasy,Toamasina',
            str_replace(PHP_EOL,',',$driver->get())
        );
    }

    public function testShowReturnsCacheItemStructure(): void
    {
        $item = $this->createStringItem('show-cache');

        $item->setData([
            'NCache',
        ]);

        $driver = new StringCache($item);

        self::assertSame(
            $item->toArray(),
            $driver->show()
        );
    } 

    public function testSaveReplacesExistingContent(): void
    {
        $firstItem = $this->createStringItem('replace-cache');
        $firstItem->setData(['version-one']);

        (new StringCache($firstItem))->save();

        $secondItem = $this->createStringItem('replace-cache');
        $secondItem->setData(['version-two']);

        $driver = new StringCache($secondItem);
        $driver->save();

        $content = $driver->get();

        self::assertStringContainsString(
            'version-two',
            $content
        );

        self::assertStringNotContainsString(
            'version-one;',
            $content
        );
    }

    public function testDeleteRemovesSavedFile(): void
    {
        $item = $this->createStringItem('delete-cache');
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
            $this->createStringItem('missing-delete')
        );

        self::assertFileDoesNotExist($driver->getFile());
        self::assertTrue($driver->delete());
    }

    public function testClearDeletesAllTxtCaches(): void
    {
        $firstItem = $this->createStringItem('first');
        $firstItem->setData(['one']);

        $secondItem = $this->createStringItem('second');
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
        $item = $this->createStringItem('string-cache');
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


}