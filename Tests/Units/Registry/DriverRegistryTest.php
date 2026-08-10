<?php

declare(strict_types=1);

namespace NCache\Tests\Units\Registry;

use NCache\Core\CacheItem\CacheItem;
use NCache\Driver\CacheDriver;
use NCache\Driver\JsonCache;
use NCache\Driver\SerializeCache;
use NCache\Driver\StringCache;
use NCache\Enum\CType;
use NCache\Exceptions\InvalidCacheArgumentException;
use NCache\Registry\DriverRegistry;
use NCache\Tests\TestsUnit\TestsUnit;

final class DriverRegistryTest extends TestsUnit
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->directory('ncache-driver-registry-');
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->directory);
        parent::tearDown();
    }

    public function testMakeReturnsJsonCache(): void
    {
        $driver = DriverRegistry::make(
            $this->DrItem(CType::JSON)
        );

        self::assertInstanceOf(JsonCache::class, $driver);
        self::assertInstanceOf(CacheDriver::class, $driver);
    }

    public function testMakeReturnsSerializeCache(): void
    {
        $driver = DriverRegistry::make(
            $this->DrItem(CType::SERIALIZE)
        );

        self::assertInstanceOf(SerializeCache::class, $driver);
        self::assertInstanceOf(CacheDriver::class, $driver);
    }

    public function testMakeReturnsStringCache(): void
    {
        $driver = DriverRegistry::make(
            $this->DrItem(CType::STRING)
        );

        self::assertInstanceOf(StringCache::class, $driver);
        self::assertInstanceOf(CacheDriver::class, $driver);
    }

    public function testMakeUsesTheProvidedCacheItem(): void
    {
        $item = $this->DrItem(CType::JSON);

        $item->setData([
            'name' => 'Noga',
        ]);

        $driver = DriverRegistry::make($item);

        self::assertSame(
            $item->toArray(),
            $driver->show()
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

    private function DrItem(CType $type): CacheItem
    {
        return $this->createItem('registry-cache', $type);
    }
}
