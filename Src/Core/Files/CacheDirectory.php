<?php

declare(strict_types=1);

namespace NCache\Core\Files;

use FilesystemIterator;
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
        private readonly array $directory
    ) {}

    /**
     * @param list<string> $directory
     *
     * @return list<RecursiveIteratorIterator<RecursiveDirectoryIterator>>
     */
    private function recursiveDirectory(array $directory): array
    {
        /**
        *
        * @var list<RecursiveIteratorIterator<RecursiveDirectoryIterator>>
        */
        $recursive = [];

        foreach ($directory as $dir) {
            $this->isValidDirectory($dir);

            $directoryIterator
                = new RecursiveDirectoryIterator(
                    $dir,
                    FilesystemIterator::SKIP_DOTS
                );

            $recursive[]
                = new RecursiveIteratorIterator(
                    $directoryIterator,
                    RecursiveIteratorIterator::CHILD_FIRST
                );
        }

        return $recursive;
    }

    private function isValidDirectory(string $directory): void
    {
        if (!is_dir($directory)) {
            throw new InvalidCacheArgumentException(
                "cannot find a directory on {$directory}"
            );
        }
    }

    /**
     * @return list<SplFileInfo>
     */
    public function iterate(): array
    {
        $iterators = $this->recursiveDirectory($this->directory);

        /**
         * @var list<SplFileInfo>
         */
        $iterate = [];

        foreach ($iterators as $iterator) {
            foreach ($iterator as $file) {
                if (!$file instanceof SplFileInfo) {
                    continue;
                }

                $iterate[] = $file;
            }
        }

        return $iterate;
    }
}
