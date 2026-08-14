<?php

declare(strict_types=1);

namespace NCache\Tests\Units\Config;

use NCache\Config\CacheConfig;
use NCache\Config\ConfigItem;
use NCache\Exceptions\UnexpectedConfigException;
use NCache\Tests\TestsUnit\TestsUnit;

final class CacheConfigTest extends TestsUnit
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->directory(
            'ncache-config-',
        );
    }

    public function testConfigReturnsSingleton(): void
    {
        $first = CacheConfig::config(
            $this->configFile,
        );

        $second = CacheConfig::config(
            $this->configFile,
        );

        self::assertSame(
            $first,
            $second,
        );
    }

    public function testConfigKeepsFirstInstanceWhenCalledAgain(): void
    {
        $first = CacheConfig::config(
            $this->configFile,
        );

        $secondFile = $this->createConfigFile(
            'second.config.json',
            [
                'other' => $this->profile([
                    'cachePath' => './other',
                ]),
            ],
        );

        $second = CacheConfig::config(
            $secondFile,
        );

        self::assertSame(
            $first,
            $second,
        );
    }

    public function testUseSelectsProfile(): void
    {
        $config = CacheConfig::config(
            $this->configFile,
        )->use('default');

        self::assertSame(
            'default',
            $config->profile(),
        );
    }

    public function testCanSwitchProfiles(): void
    {
        $config = CacheConfig::config(
            $this->configFile,
        );

        $config->use('default');

        self::assertSame(
            'default',
            $config->profile(),
        );

        $config->use('users');

        self::assertSame(
            'users',
            $config->profile(),
        );

        $config->use('default');

        self::assertSame(
            'default',
            $config->profile(),
        );
    }

    public function testUseThrowsWhenProfileDoesNotExist(): void
    {
        $config = CacheConfig::config(
            $this->configFile,
        );

        $this->expectException(
            UnexpectedConfigException::class,
        );

        $this->expectExceptionMessage(
            'Undefined cache profile: missing',
        );

        $config->use('missing');
    }

    public function testStateReturnsConfigItem(): void
    {
        $config = CacheConfig::config(
            $this->configFile,
        )->use('default');

        $state = $config->state();

        self::assertInstanceOf(
            ConfigItem::class,
            $state,
        );

        self::assertSame(
            'default',
            $state->profile(),
        );
    }

    public function testStateCanResolveExplicitProfile(): void
    {
        $config = CacheConfig::config(
            $this->configFile,
        )->use('default');

        $state = $config->state('users');

        self::assertSame(
            'users',
            $state->profile(),
        );

        // Important : state('users') ne doit pas
        // modifier le profil actif.
        self::assertSame(
            'default',
            $config->profile(),
        );
    }

    public function testStateIsIndependentFromLaterProfileSwitch(): void
    {
        $config = CacheConfig::config(
            $this->configFile,
        )->use('default');

        $state = $config->state();

        $config->use('users');

        self::assertSame(
            'default',
            $state->profile(),
        );

        self::assertSame(
            'users',
            $config->profile(),
        );
    }

    public function testStateKeepsItsResolvedConfiguration(): void
    {
        $config = CacheConfig::config(
            $this->configFile,
        )->use('default');

        $state = $config->state();

        $path = $state->getBasePath();
        $driver = $state->getDefaultDriver();

        $config->use('users');

        self::assertSame(
            $path,
            $state->getBasePath(),
        );

        self::assertSame(
            $driver,
            $state->getDefaultDriver(),
        );
    }

    public function testStateThrowsForUnknownProfile(): void
    {
        $config = CacheConfig::config(
            $this->configFile,
        );

        $this->expectException(
            UnexpectedConfigException::class,
        );

        $this->expectExceptionMessage(
            'Undefined cache profile: missing',
        );

        $config->state('missing');
    }

    public function testStateWithoutSelectedProfileThrowsException(): void
    {
        $config = CacheConfig::config(
            $this->configFile,
        );

        $this->expectException(
            UnexpectedConfigException::class,
        );

        $config->state();
    }

    public function testDriversFromIsResolvedIntoState(): void
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
            ],
        );

        CacheConfig::resetInstance();

        $state = CacheConfig::config($file)
            ->use('users')
            ->state();

        self::assertSame(
            '127.0.0.1',
            $state->getRedis()['host'],
        );

        self::assertSame(
            6379,
            $state->getRedis()['port'],
        );

        self::assertSame(
            11211,
            $state->getMemcached()['port'],
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
            ],
        );

        CacheConfig::resetInstance();

        $config = CacheConfig::config(
            $file,
        );

        $this->expectException(
            UnexpectedConfigException::class,
        );

        $config->use('users');
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
            ],
        );

        CacheConfig::resetInstance();

        $config = CacheConfig::config(
            $file,
        );

        $this->expectException(
            UnexpectedConfigException::class,
        );

        $config->use('one');
    }

    public function testMissingConfigurationFileThrowsException(): void
    {
        $missing = $this->directory
            . DIRECTORY_SEPARATOR
            . 'missing.json';

        CacheConfig::resetInstance();

        $this->expectException(
            UnexpectedConfigException::class,
        );

        CacheConfig::config(
            $missing,
        );
    }

    protected function tearDown(): void
    {
        CacheConfig::resetInstance();

        if (isset($this->directory)) {
            $this->removeDirectory(
                $this->directory,
            );
        }

        parent::tearDown();
    }

    /**
     * @param array<string,mixed> $override
     * @return array<string,mixed>
     */
    private function profile(
        array $override = [],
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
            $override,
        );
    }

    /**
     * @param array<string,array<string,mixed>> $config
     */
    private function createConfigFile(
        string $name,
        array $config,
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
                        | JSON_THROW_ON_ERROR,
                ),
            ),
        );

        return $file;
    }
}
