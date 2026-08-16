<?php

declare(strict_types=1);

namespace NCache\Core\Files;

use finfo;
use JsonException;
use NCache\Enum\CType;
use NCache\Exceptions\FailedReadCacheException;
use Throwable;

final class ReadFile
{
    public function __construct(
        private readonly string $file,
        private readonly CType $type,
    ) {
    }

    /**
     * @return array<array-key,mixed>|string
     */
    public function get(): array|string
    {
        return match ($this->type) {
            CType::SERIALIZE => $this->loadSerializedArray(),
            CType::JSON => $this->decodeJson(),
            CType::ARRAY_PHP => $this->loadArrayFilePhp(),
            CType::STRING => $this->read(),
            default => $this->read(),
        };
    }

    private function read(): string
    {
        $handle = fopen($this->file, 'rb');

        if ($handle === false) {
            throw new FailedReadCacheException(
                "Unable to open file: '{$this->file}'.",
            );
        }

        $locked = false;

        try {
            if (!flock($handle, LOCK_SH)) {
                throw new FailedReadCacheException(
                    "Unable to lock file: '{$this->file}'.",
                );
            }

            $locked = true;

            $content = stream_get_contents(
                $handle,
            );

            if ($content === false) {
                throw new FailedReadCacheException(
                    "Unable to read file: '{$this->file}'.",
                );
            }

            return $content;
        } finally {
            if ($locked) {
                flock(
                    $handle,
                    LOCK_UN,
                );
            }

            fclose($handle);
        }
    }

    /**
     * @return array<array-key,mixed>
     */
    private function decodeJson(): array
    {
        try {
            $data = json_decode(
                $this->read(),
                true,
                512,
                JSON_THROW_ON_ERROR,
            );
        } catch (JsonException $exception) {
            throw new FailedReadCacheException(
                "Invalid JSON cache file: '{$this->file}'.",
                previous: $exception,
            );
        }

        if (!\is_array($data)) {
            throw new FailedReadCacheException(
                "JSON cache file must contain an array: '{$this->file}'.",
            );
        }

        return $data;
    }

    /**
     * @return array<array-key,mixed>
     */
    private function loadSerializedArray(): array
    {
        $content = $this->read();

        try {
            $data = @unserialize(
                $content,
                [
                    'allowed_classes' => false,
                ],
            );

            if (
                $data === false &&
                $content !== serialize(false)
            ) {
                throw new FailedReadCacheException(
                    "Invalid serialized cache data: '{$this->file}'.",
                );
            }
        } catch (Throwable $exception) {
            if ($exception instanceof FailedReadCacheException) {
                throw $exception;
            }

            throw new FailedReadCacheException(
                "Serialized cache file is corrupted: '{$this->file}'.",
                previous: $exception,
            );
        }

        if (!\is_array($data)) {
            throw new FailedReadCacheException(
                "Serialized cache file must contain an array: '{$this->file}'.",
            );
        }

        return $data;
    }

    /**
     * @return array<array-key, mixed>
     */
    private function loadArrayFilePhp(): array
    {
        $finfo = new finfo(FILEINFO_MIME_TYPE);

        $mime = $finfo->file($this->file);

        if ($mime === false || $mime !== 'text/x-php') {
            throw new FailedReadCacheException(
                "Invalid PHP cache file: '{$this->file}'.",
            );
        }

        try {
            $content = require $this->file;
        } catch (Throwable $exception) {
            throw new FailedReadCacheException(
                "Unable to load PHP cache file: '{$this->file}'.",
                previous: $exception,
            );
        }

        if (!\is_array($content)) {
            throw new FailedReadCacheException(
                "PHP cache file must return an array: '{$this->file}'.",
            );
        }

        return $content;
    }
}
