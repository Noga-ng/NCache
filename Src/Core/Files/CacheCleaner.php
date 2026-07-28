<?php
declare(strict_types=1);

namespace NCache\Core\Files;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

final class CacheCleaner
{

    /**
     * @param string[] $extensionAllowed
     */
    public function __construct(
        private readonly array $extensionAllowed
        ){}

    /**
     * @return string[]
     */
    public function extensionAllowed():array{
        return $this->extensionAllowed;
    }
    public function delete(string $filename): bool
    {
        if (!is_file($filename)) {
            return true;
        }

        return unlink($filename);
    }

  public function clear(string $dir): int
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

        if (
            $file->isFile() && !$file->isLink() && 
            \in_array($file->getExtension(),$this->extensionAllowed,true)
            ) {
            if (unlink($realPath)) {
                $count++;
            }
        }

    }

    return $count;
}

}