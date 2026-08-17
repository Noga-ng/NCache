<?php

declare(strict_types=1);

namespace NCache\Core\Files;

use FilesystemIterator;
use Generator;
use NCache\Exceptions\InvalidCacheArgumentException;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

final class CacheDirectory
{
    /**
     * @param list<string> $directory
     */
    public function __construct(
        private readonly array $directory,
    ) {
    }


    /**
 * @return Generator<int, SplFileInfo>
 */
    public function iterate(): Generator
    {
        foreach ($this->directory as $dir) {
            $this->isValidDirectory($dir);

            $directoryIterator = new RecursiveDirectoryIterator(
                $dir,
                FilesystemIterator::SKIP_DOTS,
            );

            $iterator = new RecursiveIteratorIterator(
                $directoryIterator,
                RecursiveIteratorIterator::CHILD_FIRST,
            );

            foreach ($iterator as $file) {
                if (!$file instanceof SplFileInfo) {
                    continue;
                }

                yield $file;
            }
        }
    }

    private function isValidDirectory(string $directory): void
    {
        if (!is_dir($directory)) {
            throw new InvalidCacheArgumentException(
                "cannot find a directory on {$directory}",
            );
        }
    }

}
