<?php

declare(strict_types=1);

namespace NCache\Tests\TestsUnit;

use NCache\Config\CacheConfig;
use NCache\Contract\Clock;
use NCache\Core\CacheItem\CacheItem;
use NCache\Core\Clock\SystemClock;
use NCache\Core\Files\CacheCleaner;
use NCache\Core\Files\CacheDirectory;
use NCache\Core\Files\ReadFile;
use NCache\Core\Files\WriteFile;
use NCache\Enum\CType;
use PHPUnit\Framework\TestCase;
use SplFileInfo;

abstract class TestsUnit extends TestCase
{
    protected string $directory;

    protected string $configFile;

    protected function setUp(): void
    {
        parent::setUp();

        CacheConfig::resetInstance();
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

    protected function directory(string $prefix): void
    {
        $this->directory = sys_get_temp_dir()
            . DIRECTORY_SEPARATOR
            . $prefix
            . bin2hex(
                random_bytes(8),
            );

        self::assertTrue(
            mkdir(
                $this->directory,
                0o777,
                true,
            ),
        );

        $this->createDefaultConfig();
    }

    /**
 * @param list<SplFileInfo> $files
 * @return list<SplFileInfo>
 */
    private function withoutConfigFile(array $files): array
    {
        return array_values(
            array_filter(
                $files,
                static fn (SplFileInfo $file): bool
                    => $file->getFilename() !== 'ncache.config.json',
            ),
        );
    }

    protected function clock(): Clock
    {
        return new SystemClock();
    }

    protected function config(): CacheConfig
    {
        return CacheConfig::config(
            $this->configFile,
        )->use('default');
    }

    protected function createItem(
        string $key,
        CType $type = CType::JSON,
    ): CacheItem {
        return new CacheItem(
            $key,
            $type,
            $this->config()->state(),
        );
    }

    protected function createJsonItem(
        string $key,
    ): CacheItem {
        return $this->createItem(
            $key,
            CType::JSON,
        );
    }

    protected function createSerializeItem(
        string $key,
    ): CacheItem {
        return $this->createItem(
            $key,
            CType::SERIALIZE,
        );
    }

    protected function createSQLiteItem(
        string $key,
    ): CacheItem {
        return $this->createItem(
            $key,
            CType::SQLite,
        );
    }

    protected function createRedisItem(
        string $key,
    ): CacheItem {
        return $this->createItem(
            $key,
            CType::REDIS,
        );
    }

    protected function createStringItem(
        string $key,
    ): CacheItem {
        return $this->createItem(
            $key,
            CType::STRING,
        );
    }

    protected function createMemCachedItem(
        string $key,
    ): CacheItem {
        return $this->createItem(
            $key,
            CType::MEMCACHED,
        );
    }

    protected function createDefaultConfig(): void
    {
        $this->configFile = $this->directory
            . DIRECTORY_SEPARATOR
            . 'ncache.config.json';

        $config = [
            'default' => [
                'cachePath' => './cache',
                'defaultDriver' => 'JSON',
                'namespace' => null,

                'extensions' => [
                    'JSON' => 'json',
                    'SERIALIZE' => 'nc',
                    'STRING' => 'txt',
                ],

                'defaultTags'=>["default.tag"],
                'defaultTtl' => 'hours(1)',

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
            'users' => [
                'cachePath' => './cache',
                'defaultDriver' => 'JSON',
                'namespace' => null,

                'extensions' => [
                    'JSON' => 'json',
                    'SERIALIZE' => 'nc',
                    'STRING' => 'txt',
                ],
                
                'defaultTags'=>["users.tag"],
                'defaultTtl' => 'hours(1)',
                'driversFrom' => 'default',
            ],
        ];

        $json = json_encode(
            $config,
            JSON_PRETTY_PRINT
            | JSON_THROW_ON_ERROR,
        );

        $this->writeFile(
            $this->configFile,
            $json,
        )->save();
    }

    /**
     * @param string[]|string $directory
     */
    protected function cacheDirectory(
        array|string $directory,
    ): CacheDirectory {
        $dir = is_array($directory)
            ? $directory
            : [$directory];

        return new CacheDirectory(
            $dir,
        );
    }

    protected function createFile(
        string $name,
        string $content = 'cache',
    ): string {
        $file = $this->directory
            . DIRECTORY_SEPARATOR
            . $name;

        $dir = dirname(
            $file,
        );

        if (!is_dir($dir)) {
            mkdir(
                $dir,
                0o777,
                true,
            );
        }

        @$this
            ->writeFile(
                $file,
                $content,
            )
            ->save();

        return $file;
    }

    protected function readFile(
        string $filename,
        CType $type,
    ): ReadFile {
        return new ReadFile(
            $filename,
            $type,
        );
    }

    protected function writeFile(
        string $filename,
        string $content = 'write',
    ): WriteFile {
        return new WriteFile(
            $filename,
            $content,
        );
    }

    /**
     * @param string[]|string $ext
     */
    protected function cacheCleaner(
        array|string $ext,
    ): CacheCleaner {
        $extension = is_array($ext)
            ? $ext
            : [$ext];

        return new CacheCleaner(
            $extension,
        );
    }

    protected function removeDirectory(
        string $directory,
    ): void {
        if (!is_dir($directory)) {
            return;
        }

        $items = scandir(
            $directory,
        );

        if ($items === false) {
            return;
        }

        foreach ($items as $item) {
            if (
                $item === '.'
                || $item === '..'
            ) {
                continue;
            }

            $path = $directory
                . DIRECTORY_SEPARATOR
                . $item;

            if (
                is_dir($path)
                && !is_link($path)
            ) {
                $this->removeDirectory(
                    $path,
                );

                continue;
            }

            @unlink($path);
        }

        @rmdir($directory);
    }
}
