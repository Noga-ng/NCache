<?php

declare(strict_types=1);

namespace NCache\Tests\Units\Config;

use NCache\Config\CacheConfig;
use NCache\Core\Clock\Duration;
use NCache\Enum\CType;
use NCache\Exceptions\UnexpectedConfigException;
use NCache\Tests\TestsUnit\TestsUnit;
use Override;

final class CacheConfigTest extends TestsUnit
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->directory(
            'ncache-config-'
        );
    }

    public function testConfigReturnsSingleton(): void
    {
        $first = CacheConfig::config(
            $this->configFile
        );

        $second = CacheConfig::config(
            $this->configFile
        );

        self::assertSame(
            $first,
            $second
        );
    }

    public function testConfigKeepsFirstInstanceWhenCalledAgain(): void
    {
        $first = CacheConfig::config(
            $this->configFile
        );

        $secondFile = $this->createConfigFile(
            'second.config.json',
            [
                'other' => $this->profile([
                    'cachePath' => './other',
                ]),
            ]
        );

        $second = CacheConfig::config(
            $secondFile
        );

        self::assertSame(
            $first,
            $second
        );
    }

    public function testUseSelectsProfile(): void
    {
        $config = CacheConfig::config(
            $this->configFile
        )->use('default');

        self::assertSame(
            'default',
            $config->profile()
        );
    }

    public function testUseThrowsWhenProfileDoesNotExist(): void
    {
        $config = CacheConfig::config(
            $this->configFile
        );

        $this->expectException(
            UnexpectedConfigException::class
        );

        $this->expectExceptionMessage(
            'Undefined cache profile: missing'
        );

        $config->use(
            'missing'
        );
    }

    public function testGetBasePathResolvesRelativePathFromConfigDirectory(): void
    {
        $config = CacheConfig::config(
            $this->configFile
        )->use('default');

        self::assertSame(
            $this->directory
                . DIRECTORY_SEPARATOR
                . 'cache',
            $config->getBasePath()
        );
    }

    public function testAbsoluteCachePathIsPreserved(): void
    {
        $absolute = $this->directory
            . DIRECTORY_SEPARATOR
            . 'absolute-cache';

        $file = $this->createConfigFile(
            'absolute.config.json',
            [
                'absolute' => $this->profile([
                    'cachePath' => $absolute,
                ]),
            ]
        );

        CacheConfig::resetInstance();

        $config = CacheConfig::config(
            $file
        )->use('absolute');

        self::assertSame(
            $absolute,
            $config->getBasePath()
        );
    }

    public function testGetDefaultDriverReturnsConfiguredEnum(): void
    {
        $config = CacheConfig::config(
            $this->configFile
        )->use('default');

        self::assertSame(
            CType::JSON,
            $config->getDefaultDriver()
        );
    }

    public function testDefaultDriverCanBeNull(): void
    {
        $file = $this->createConfigFile(
            'no-driver.config.json',
            [
                'no-driver' => $this->profile([
                    'defaultDriver' => null,
                ]),
            ]
        );

        CacheConfig::resetInstance();

        $config = CacheConfig::config(
            $file
        )->use('no-driver');

        self::assertNull(
            $config->getDefaultDriver()
        );
    }

    public function testInvalidDefaultDriverThrowsException(): void
    {
        $file = $this->createConfigFile(
            'invalid-driver.config.json',
            [
                'invalid' => $this->profile([
                    'defaultDriver' => 'REDISS',
                ]),
            ]
        );

        CacheConfig::resetInstance();

        $this->expectException(
            UnexpectedConfigException::class
        );

        CacheConfig::config(
            $file
        );
    }

    public function testGetNamespaceReturnsConfiguredNamespace(): void
    {
        $config = CacheConfig::config(
            $this->configFile
        )->use('default');

        self::assertNull(
            $config->getNamespace()
        );
    }

    public function testExtensionIsResolvedFromConfiguration(): void
    {
        $config = CacheConfig::config(
            $this->configFile
        )->use('default');

        self::assertSame(
            'nc',
            $config->getExtension(
                CType::SERIALIZE
            )
        );

        self::assertSame(
            'txt',
            $config->getExtension(
                CType::STRING
            )
        );
    }

    public function testMissingExtensionReturnsNull(): void
    {
        $config = CacheConfig::config(
            $this->configFile
        )->use('default');

        self::assertNull(
            $config->getExtension(
                CType::REDIS
            )
        );
    }

    public function testIntegerDefaultTtlIsPreserved(): void
    {
        $file = $this->createConfigFile(
            'integer-ttl.config.json',
            [
                'ttl' => $this->profile([
                    'defaultTtl' => 120,
                ]),
            ]
        );

        CacheConfig::resetInstance();

        $config = CacheConfig::config(
            $file
        )->use('ttl');

        self::assertSame(
            120,
            $config->getDefaultTtl()
        );
    }

    public function testDefaultTtlHoursExpressionIsResolved(): void
    {
        $file = $this->createConfigFile(
            'hours-ttl.config.json',
            [
                'ttl' => $this->profile([
                    'defaultTtl' => 'hours(2)',
                ]),
            ]
        );

        CacheConfig::resetInstance();

        $config = CacheConfig::config(
            $file
        )->use('ttl');

        self::assertSame(
            Duration::hours(2),
            $config->getDefaultTtl()
        );
    }

    public function testDefaultTtlDaysExpressionIsResolved(): void
    {
        $file = $this->createConfigFile(
            'days-ttl.config.json',
            [
                'ttl' => $this->profile([
                    'defaultTtl' => 'days(2)',
                ]),
            ]
        );

        CacheConfig::resetInstance();

        $config = CacheConfig::config(
            $file
        )->use('ttl');

        self::assertSame(
            Duration::days(2),
            $config->getDefaultTtl()
        );
    }

    public function testDefaultTtlMakeExpressionIsResolved(): void
    {
        $file = $this->createConfigFile(
            'make-ttl.config.json',
            [
                'ttl' => $this->profile([
                    'defaultTtl' => 'make(1,10,15,25)',
                ]),
            ]
        );

        CacheConfig::resetInstance();

        $config = CacheConfig::config(
            $file
        )->use('ttl');

        self::assertSame(
            Duration::make(
                1,
                10,
                15,
                25
            ),
            $config->getDefaultTtl()
        );
    }

    public function testNullDefaultTtlRemainsNull(): void
    {
        $file = $this->createConfigFile(
            'null-ttl.config.json',
            [
                'ttl' => $this->profile([
                    'defaultTtl' => null,
                ]),
            ]
        );

        CacheConfig::resetInstance();

        $config = CacheConfig::config(
            $file
        )->use('ttl');

        self::assertNull(
            $config->getDefaultTtl()
        );
    }

    public function testInvalidTtlExpressionThrowsException(): void
    {
        $file = $this->createConfigFile(
            'invalid-ttl.config.json',
            [
                'ttl' => $this->profile([
                    'defaultTtl' => 'hours(foo)',
                ]),
            ]
        );

        CacheConfig::resetInstance();

        $config = CacheConfig::config(
            $file
        );

        $this->expectException(
            UnexpectedConfigException::class
        );

        $config->use(
            'ttl'
        );
    }

    public function testUndefinedDurationMethodThrowsException(): void
    {
        $file = $this->createConfigFile(
            'unknown-ttl.config.json',
            [
                'ttl' => $this->profile([
                    'defaultTtl' => 'years(2)',
                ]),
            ]
        );

        CacheConfig::resetInstance();

        $config = CacheConfig::config(
            $file
        );

        $this->expectException(
            UnexpectedConfigException::class
        );

        $config->use(
            'ttl'
        );
    }

    public function testDriversFromInheritsDriversFromAnotherProfile(): void
    {
        $file = $this->createConfigFile(
            'drivers-from.config.json',
            [
                'shared' => $this->profile(),
                'users' => $this->profile([
                    'cachePath' => './users',
                    'defaultDriver' => 'REDIS',
                    'drivers' => [],
                    'driversFrom' => 'shared',
                ]),
            ]
        );

        CacheConfig::resetInstance();

        $config = CacheConfig::config(
            $file
        )->use('users');

        self::assertSame(
            '127.0.0.1',
            $config->getRedis()['host']
        );

        self::assertSame(
            6379,
            $config->getRedis()['port']
        );

        self::assertSame(
            11211,
            $config->getMemcached()['port']
        );
    }

    public function testDriversFromRuntimeOverrideUsesSelectedProfileDrivers(): void
    {
        $file = $this->createConfigFile(
            'drivers-runtime.config.json',
            [
                'shared' => $this->profile(),
                'users' => $this->profile([
                    'drivers' => [],
                ]),
            ]
        );

        CacheConfig::resetInstance();

        $config = CacheConfig::config(
            $file
        )
            ->use('users')
            ->driversFrom('shared');

        self::assertNotNull(
            $config->getRedis()
        );

        self::assertNotNull(
            $config->getMemcached()
        );
    }

    public function testDriversFromUnknownProfileThrowsException(): void
    {
        $file = $this->createConfigFile(
            'drivers-missing.config.json',
            [
                'users' => $this->profile([
                    'driversFrom' => 'missing',
                ]),
            ]
        );

        CacheConfig::resetInstance();

        $config = CacheConfig::config(
            $file
        );

        $this->expectException(
            UnexpectedConfigException::class
        );

        $config->use(
            'users'
        );
    }

    public function testDriversFromSelfThrowsException(): void
    {
        $file = $this->createConfigFile(
            'drivers-self.config.json',
            [
                'users' => $this->profile([
                    'driversFrom' => 'users',
                ]),
            ]
        );

        CacheConfig::resetInstance();

        $config = CacheConfig::config(
            $file
        );

        $this->expectException(
            UnexpectedConfigException::class
        );

        $config->use(
            'users'
        );
    }

    public function testCircularDriversFromThrowsException(): void
    {
        $file = $this->createConfigFile(
            'drivers-circular.config.json',
            [
                'one' => $this->profile([
                    'driversFrom' => 'two',
                ]),
                'two' => $this->profile([
                    'driversFrom' => 'one',
                ]),
            ]
        );

        CacheConfig::resetInstance();

        $config = CacheConfig::config(
            $file
        );

        $this->expectException(
            UnexpectedConfigException::class
        );

        $config->use(
            'one'
        );
    }

    public function testRedisConfigurationIsNormalized(): void
    {
        $config = CacheConfig::config(
            $this->configFile
        )->use('default');

        self::assertSame(
            [
                'host' => '127.0.0.1',
                'port' => 6379,
                'timeout' => 0,
                'password' => null,
                'database' => 0,
            ],
            $config->getRedis()
        );
    }

    public function testMemcachedConfigurationIsNormalized(): void
    {
        $config = CacheConfig::config(
            $this->configFile
        )->use('default');

        self::assertSame(
            [
                'host' => '127.0.0.1',
                'port' => 11211,
                'weight' => 0,
            ],
            $config->getMemcached()
        );
    }

    public function testGetDataReturnsResolvedProfile(): void
    {
        $config = CacheConfig::config(
            $this->configFile
        )->use('default');

        $data = $config->getData();

        self::assertSame(
            $config->getBasePath(),
            $data['cachePath']
        );

        self::assertSame(
            'JSON',
            $data['defaultDriver']
        );

        self::assertNull(
            $data['namespace']
        );

        self::assertIsArray(
            $data['drivers']
        );
    }

    public function testGetAllReturnsAllNormalizedProfiles(): void
    {
        $config = CacheConfig::config(
            $this->configFile
        );

        $all = $config->getAll();

        self::assertArrayHasKey(
            'default',
            $all
        );
    }

    public function testProfileThrowsWhenNoProfileWasSelected(): void
    {
        $config = CacheConfig::config(
            $this->configFile
        );

        $this->expectException(
            UnexpectedConfigException::class
        );

        $config->profile();
    }

    public function testGetBasePathThrowsWhenNoProfileWasSelected(): void
    {
        $config = CacheConfig::config(
            $this->configFile
        );

        $this->expectException(
            UnexpectedConfigException::class
        );

        $config->getBasePath();
    }

    public function testMissingConfigurationFileThrowsException(): void
    {
        $missing = $this->directory
            . DIRECTORY_SEPARATOR
            . 'missing.json';

        CacheConfig::resetInstance();

        $this->expectException(
            UnexpectedConfigException::class
        );

        CacheConfig::config(
            $missing
        );
    }

    #[Override]
    protected function tearDown(): void
    {
        CacheConfig::resetInstance();

        if (isset($this->directory)) {
            $this->removeDirectory(
                $this->directory
            );
        }

        parent::tearDown();
    }

    /**
     * @param array<string,mixed> $override
     * @return array<string,mixed>
     */
    private function profile(
        array $override = []
    ): array {
        return array_replace_recursive(
            [
                'cachePath' => './cache',
                'defaultDriver' => 'JSON',
                'namespace' => 'default',
                'extensions' => [
                    'JSON' => 'json',
                    'SERIALIZE' => 'nc',
                    'STRING' => 'txt',
                ],
                'defaultTtl' => null,
                'drivers' => [
                    'redis' => [
                        'host' => '127.0.0.1',
                        'port' => 6379,
                        'timeout' => 0,
                        'password' => null,
                        'database' => 0,
                    ],
                    'memcached' => [
                        'host' => '127.0.0.1',
                        'port' => 11211,
                        'weight' => 0,
                    ],
                ],
            ],
            $override
        );
    }

    /**
     * @param array<string,array<string,mixed>> $config
     */
    private function createConfigFile(
        string $name,
        array $config
    ): string {
        $file = $this->directory
            . DIRECTORY_SEPARATOR
            . $name;

        self::assertNotFalse(
            file_put_contents(
                $file,
                json_encode(
                    $config,
                    JSON_PRETTY_PRINT
                        | JSON_THROW_ON_ERROR
                )
            )
        );

        return $file;
    }
}
