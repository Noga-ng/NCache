<?php

declare(strict_types=1);

namespace NCache\Tests\Units\Registry;

use NCache\Core\CacheItem\CacheItem;
use NCache\Core\CachePath;
use NCache\Driver\CacheDriver;
use NCache\Driver\JsonCache;
use NCache\Driver\SerializeCache;
use NCache\Driver\StringCache;
use NCache\Enum\CType;
use NCache\Exceptions\InvalidCacheArgumentException;
use NCache\Registry\DriverRegistry;
use PHPUnit\Framework\TestCase;

final class DriverRegistryTest extends TestCase
{
    private string $directory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->directory = sys_get_temp_dir()
            . DIRECTORY_SEPARATOR
            . 'ncache-driver-registry-'
            . bin2hex(random_bytes(8));
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->directory);

        parent::tearDown();
    }

    public function testMakeReturnsJsonCache(): void
    {
        $driver = DriverRegistry::make(
            $this->createItem(CType::JSON)
        );

        self::assertInstanceOf(JsonCache::class, $driver);
        self::assertInstanceOf(CacheDriver::class, $driver);
    }

    public function testMakeReturnsSerializeCache(): void
    {
        $driver = DriverRegistry::make(
            $this->createItem(CType::SERIALIZE)
        );

        self::assertInstanceOf(SerializeCache::class, $driver);
        self::assertInstanceOf(CacheDriver::class, $driver);
    }

    public function testMakeReturnsStringCache(): void
    {
        $driver = DriverRegistry::make(
            $this->createItem(CType::STRING)
        );

        self::assertInstanceOf(StringCache::class, $driver);
        self::assertInstanceOf(CacheDriver::class, $driver);
    }

    public function testMakeUsesTheProvidedCacheItem(): void
    {
        $item = $this->createItem(CType::JSON);

        $item->setData([
            'name' => 'Noga',
        ]);

        $driver = DriverRegistry::make($item);

        self::assertSame(
            $item->toArray(),
            $driver->show()
        );
    }

    public function testMakeThrowsExceptionForUnregisteredType(): void
    {
        $this->expectException(
            InvalidCacheArgumentException::class
        );

        $this->expectExceptionMessage(
            'No driver registered for MEMCACHED'
        );

        DriverRegistry::make(
            $this->createItem(CType::MEMCACHED)
        );
    }

    public function testRegisterRejectsClassThatDoesNotExtendCacheDriver(): void
    {
        $this->expectException(
            InvalidCacheArgumentException::class
        );

        $this->expectExceptionMessage(
            \stdClass::class . ' must extend CacheDriver'
        );

        DriverRegistry::register(
            CType::JSON,
            \stdClass::class
        );
    }

    private function createItem(CType $type): CacheItem
    {
        return new CacheItem(
            'registry-cache',
            $type,
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