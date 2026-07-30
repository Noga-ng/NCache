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
     * Fichiers et répertoires trouvés.
     *
     * @var list<SplFileInfo>
     */
    private array $iterate = [];

    /**
     * Itérateurs récursifs associés aux répertoires.
     *
     * @var list<RecursiveIteratorIterator<RecursiveDirectoryIterator>>
     */
    private array $recursive = [];

    /**
     * @param list<string> $directory
     */
    public function __construct(
        private readonly array $directory
    ) {
        $this->handle();
    }

    public function handle(): void{
        $iterators = $this->recursiveDirectory($this->directory);

       foreach ($iterators as $iterator) {
            foreach ($iterator as $file) {
                if (!$file instanceof SplFileInfo) {
                    continue;
                }

                $this->iterate[] = $file;
            }
        }

    }

    /**
     * @param list<string> $directory
     *
     * @return list<RecursiveIteratorIterator<RecursiveDirectoryIterator>>
     */
    private function recursiveDirectory(array $directory): array
    {
        foreach ($directory as $dir) {
            if (!is_dir($dir)) {
                throw new InvalidCacheArgumentException(
                    "Directory is not valid: {$dir}"
                );
            }

      
            $directoryIterator = new RecursiveDirectoryIterator(
                $dir,
                FilesystemIterator::SKIP_DOTS
            );

            $this->recursive[] = new RecursiveIteratorIterator(
                $directoryIterator,
                RecursiveIteratorIterator::CHILD_FIRST
            );
        }

        return $this->recursive;
    }

    /**
     * @return list<SplFileInfo>
     */
    public function iterate(): array
    {
        return $this->iterate;
    }
}