<?php
declare(strict_types=1);

namespace NCache\Core\Files;

use NCache\Config\CacheConfig;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

final class CacheCleaner
{
    private readonly string $cacheDir;

    public function __construct()
    {
        $this->cacheDir = CacheConfig::config()->getBasePath();
    }

    public function delete(string $file): bool
    {
        if (!is_file($file)) {
            return true;
        }

        return unlink($file);
    }

  public function clear(?string $dir = null): int
{
    $path = $dir === null
        ? $this->cacheDir
        : $this->cacheDir . DIRECTORY_SEPARATOR . $dir;

    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator(
            $path,
            \FilesystemIterator::SKIP_DOTS
        ),
        RecursiveIteratorIterator::CHILD_FIRST
    );

    $count = 0;

    foreach ($files as $file) {
        assert($file  instanceof RecursiveDirectoryIterator);

        $realPath = $file->getRealPath();

        if ($realPath === false) {
            continue;
        }

        if ($file->isFile() && !$file->isLink()) {
            if (unlink($realPath)) {
                $count++;
            }
        } elseif ($file->isDir() && $realPath !== realpath($path)) {
            rmdir($realPath);
        }
    }

    return $count;
}

}