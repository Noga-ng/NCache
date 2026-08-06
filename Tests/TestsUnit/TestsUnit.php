<?php declare(strict_types=1);

namespace NCache\Tests\TestsUnit;

use NCache\Contract\Clock;
use NCache\Core\CacheItem\CacheItem;
use NCache\Core\Clock\SystemClock;
use NCache\Core\Files\CacheCleaner;
use NCache\Core\Files\CacheDirectory;
use NCache\Core\Files\ReadFile;
use NCache\Core\Files\WriteFile;
use NCache\Core\CachePath;
use NCache\Enum\CType;
use PHPUnit\Framework\TestCase;

abstract class TestsUnit extends TestCase
{
    protected string $directory;

    protected function directory(string $prefix): void
    {
        $this->directory = sys_get_temp_dir()
            . DIRECTORY_SEPARATOR
            . $prefix
            . bin2hex(random_bytes(8));

        self::assertTrue(
            mkdir(
                $this->directory,
                0777,
                true
            )
        );
    }

    protected function clock(): Clock
    {
        return new SystemClock();
    }

    protected function createItem(string $key, CType $type = CType::JSON): CacheItem
    {
        return new CacheItem(
            $key,
            $type,
            new CachePath(
                $this->directory
            )
        );
    }

    protected function createJsonItem(string $key): CacheItem
    {
        return $this->createItem($key, CType::JSON);
    }

    protected function createSerializeItem(string $key): CacheItem
    {
        return $this->createItem($key, CType::SERIALIZE);
    }

    protected function createSQLiteItem(string $key): CacheItem
    {
        return $this->createItem($key, CType::SQLite);
    }

    protected function createRedisItem(string $key): CacheItem
    {
        return $this->createItem($key, CType::REDIS);
    }

    protected function createStringItem(string $key): CacheItem
    {
        return $this->createItem($key, CType::STRING);
    }

    protected function createMemCachedItem(string $key): CacheItem
    {
        return $this->createItem($key, CType::MEMCACHED);
    }

    /**
     * @param string[]|string $directory
     * @return CacheDirectory
     */
    protected function cacheDirectory(array|string $directory): CacheDirectory
    {
        $dir = \is_array($directory) ? $directory : [$directory];
        return new CacheDirectory($dir);
    }

    protected function createFile(string $name, string $content = 'cache'): string
    {
        $file = $this->directory
            . DIRECTORY_SEPARATOR
            . $name;

        $dir = dirname($file);

        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        @$this->writeFile($file, $content)->save();

        return $file;
    }

    protected function readFile(string $filename, CType $type): ReadFile
    {
        return new ReadFile($filename, $type);
    }

    protected function writeFile(string $filename, string $content = 'write'): WriteFile
    {
        return new WriteFile($filename, $content);
    }

    /**
     * @param string[]|string $ext
     * @return CacheCleaner
     */
    protected function cacheCleaner(array|string $ext): CacheCleaner
    {
        $extension = \is_array($ext) ? $ext : [$ext];
        return new CacheCleaner($extension);
    }

    protected function removeDirectory(string $directory): void
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
