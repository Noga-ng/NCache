<?php

declare(strict_types=1);

namespace NCache\Tests\Units\Config;

use NCache\Config\CacheConfig;
use NCache\Tests\TestsUnit\TestsUnit;
use Override;

final class CacheConfigTest extends TestsUnit
{
    public function testConfigReturnsSingleton(): void
    {
        $first = CacheConfig::config(
            sys_get_temp_dir() . '/cache-one'
        );

        $second = CacheConfig::config(
            sys_get_temp_dir() . '/cache-two'
        );

        self::assertSame($first, $second);
    }

    public function testBasePathIsStored(): void
    {
        $this->tearDown();

        $path = sys_get_temp_dir()
            . DIRECTORY_SEPARATOR
            . 'ncache-config';

        $config = CacheConfig::config($path);

        self::assertSame(
            rtrim($path, "/\\"),
            $config->getBasePath()
        );
    }

    public function testGetBasePathAlwaysReturnsString(): void
    {
        $this->tearDown();

        $config = CacheConfig::config(
            sys_get_temp_dir()
        );

        self::assertIsString(
            $config->getBasePath()
        );
    }

    public function testEmptyConfigurationStillReturnsString(): void
    {
        $this->tearDown();

        $config = CacheConfig::config('');

        self::assertIsString(
            $config->getBasePath()
        );
    }

    public function testRepeatedCallsKeepOriginalConfiguration(): void
    {
        $this->tearDown();

        $first = CacheConfig::config(
            '/cache/one'
        );

        $original = $first->getBasePath();

        $second = CacheConfig::config(
            '/cache/two'
        );

        self::assertSame(
            $original,
            $second->getBasePath()
        );
    }

    public function testReturnedInstanceIsAlwaysCacheConfig(): void
    {
        $this->tearDown();
        self::assertInstanceOf(
            CacheConfig::class,
            CacheConfig::config(
                sys_get_temp_dir()
            )
        );
    }

    #[Override]
    public function tearDown(): void
    {
        CacheConfig::resetInstance();
        parent::tearDown();
    }
}
