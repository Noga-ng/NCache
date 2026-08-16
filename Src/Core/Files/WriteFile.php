<?php

declare(strict_types=1);

namespace NCache\Core\Files;

use NCache\Exceptions\CacheHandleException;
use NCache\Exceptions\FailedWriteCacheException;
use Throwable;

final class WriteFile
{
    private string $tmp = '';

    public function __construct(
        private readonly string $file,
        private readonly string $data,
    ) {
    }

    private function tmp(): string
    {
        $file = dirname($this->file);
        return $file
        . DIRECTORY_SEPARATOR
        . bin2hex(random_bytes(16))
        . '.tmp';
    }

    public function save(): bool
    {
        try {
            return $this->handle();
        } catch (FailedWriteCacheException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            throw new CacheHandleException(
                message: "Unexpected error while writing '{$this->file}'.",
                previous: $exception,
            );
        }
    }

    private function handle(): bool
    {
        $this->tmp = $this->tmp();

        $target = $this->tmp;
        $directory = dirname($target);

        if (!is_dir($directory)) {
            throw new FailedWriteCacheException(
                "The directory '{$directory}' does not exist.",
            );
        }

        if (!is_writable($directory)) {
            throw new FailedWriteCacheException(
                "The directory '{$directory}' is not writable.",
            );
        }

        $handle = fopen($target, 'xb');

        if ($handle === false) {
            throw new FailedWriteCacheException(
                "Cannot open file '{$target}' for writing.",
            );
        }

        $locked = false;
        $completed = false;

        try {
            $locked = flock($handle, LOCK_EX);

            if (!$locked) {
                throw new FailedWriteCacheException(
                    "Failed to lock file '{$target}'.",
                );
            }

            $this->writeAll($handle, $target);

            if (!fflush($handle)) {
                throw new FailedWriteCacheException(
                    "Failed to flush file '{$target}'.",
                );
            }

            $completed = true;

        } finally {
            if ($locked) {
                flock($handle, LOCK_UN);
            }

            fclose($handle);

            if (!$completed && is_file($this->tmp)) {
                @unlink($this->tmp);
            }
        }


        $this->replaceTarget();


        return true;
    }

    /**
     * @param resource $handle
     */
    private function writeAll(mixed $handle, string $file): void
    {
        $length = \strlen($this->data);
        $offset = 0;

        while ($offset < $length) {
            $written = fwrite(
                $handle,
                substr($this->data, $offset),
            );

            if ($written === false || $written === 0) {
                throw new FailedWriteCacheException(
                    "Failed to write all data to file '{$file}'.",
                );
            }

            $offset += $written;
        }
    }

    private function replaceTarget(): void
    {

        if (!@rename($this->tmp, $this->file)) {
            if (is_file($this->tmp)) {
                @unlink($this->tmp);
            }

            throw new FailedWriteCacheException(
                "Failed to replace the cache file.\n"
                . "Temporary file: {$this->tmp}\n"
                . "Target file: {$this->file}",
            );
        }
    }
}
