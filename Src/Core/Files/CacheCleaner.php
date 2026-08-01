<?php declare(strict_types=1);

namespace NCache\Core\Files;

use NCache\Exceptions\InvalidCacheArgumentException;

final class CacheCleaner
{
    /**
     * @param string[] $extensionAllowed
     */
    public function __construct(
        private readonly array $extensionAllowed
    ) {}

    public function delete(string $filename): bool
    {
        /** @var string */
        $ext = (string) \pathinfo($filename, \PATHINFO_EXTENSION);
        if (!is_file($filename)) {
            return true;
        }

        if (!$this->isExtensionAllowed($ext)) {
            return false;
        }

        return $this->isUnlink($filename);
    }

    public function clear(string $dir): int
    {
        $count = 0;
        $files = new CacheDirectory([$dir]);

        foreach ($files->iterate() as $file) {
            if ($file->isLink()) {
                continue;
            }

            if (
                $file->isFile() &&
                $this->isExtensionAllowed(
                    $file->getExtension()
                )
            ) {
                $this->isUnlink(
                    $file->getPathname()
                );

                $count++;
            }
        }

        return $count;
    }

    private function isUnlink(string $filename): bool
    {
        if (!\unlink($filename)) {
            throw new InvalidCacheArgumentException(
                "cannot delete this file {$filename}"
            );
        }
        return true;
    }

    public function isExtensionAllowed(string $extension): bool
    {
        return \in_array(
            $extension,
            $this->extensionAllowed,
            true
        );
    }

    /**
     * @return string[]
     */
    public function getExtensionAllowed(): array
    {
        return $this->extensionAllowed;
    }
}
