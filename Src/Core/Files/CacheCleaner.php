<?php
declare(strict_types=1);

namespace NCache\Core\Files;

use NCache\Config\CacheConfig;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

final class CacheCleaner
{

    public static function delete(string $filename): bool
    {
        if (!is_file($filename)) {
            return true;
        }
        return unlink($filename);
    }

  public static function clear(string $dir): int
{
    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator(
            $dir,
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
        } elseif ($file->isDir() && $realPath !== realpath($dir)) {
            rmdir($realPath);
        }
    }

    return $count;
}

}