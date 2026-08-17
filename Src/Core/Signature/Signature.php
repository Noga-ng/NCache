<?php

declare(strict_types=1);

namespace NCache\Core\Signature;

use NCache\Contract\SignatureInterface;
use NCache\Core\Hash;
use NCache\Registry\CacheRegistry;

/**
 * @phpstan-type EntryRegistry array{
 *     type: string,
 *     name: string,
 *     key: string,
 *     namespace: string|null,
 *     file: string|null,
 *     size: int|null,
 *     signature: string|null,
 *     ttl: int|null,
 *     expiresAt: int|null
 * }
 *
 * @phpstan-type ImprintValue array<array-key,mixed>|string|int|bool|float|null|object
 */
final class Signature implements SignatureInterface
{
    /**
     * @param ImprintValue $signature
     */
    public function __construct(
        private readonly mixed $signature,
        private readonly CacheRegistry $registry,
    ) {
    }

    public function validate(): bool
    {
        $entry = $this->registry->get();

        if ($entry === null) {
            return false;
        }

        if (!$this->fileExistsCheck($entry)) {
            return false;
        }

        if (!$this->fileSizeCheck($entry)) {
            return false;
        }

        return $this->signatureMatchCheck($entry);
    }

    /**
     * @param EntryRegistry $entry
     */
    private function signatureMatchCheck(array $entry): bool
    {
        $oldSignature = $entry['signature'];

        if ($oldSignature === null) {
            return false;
        }

        return $this->hashSignature() ===
            $oldSignature;
    }

    /**
     * @param EntryRegistry $entry
     */
    private function fileExistsCheck(array $entry): bool
    {
        $file = $entry['file'];

        if ($file === null) {
            return true;
        }

        return is_file(
            $file,
        );
    }

    /**
     * @param EntryRegistry $entry
     */
    private function fileSizeCheck(array $entry): bool
    {
        $file = $entry['file'];

        if ($file === null) {
            return true;
        }

        $registeredSize = $entry['size'];

        if ($registeredSize === null) {
            return false;
        }

        $size = filesize(
            $file,
        );

        if ($size === false) {
            return false;
        }

        return $size === $registeredSize;
    }

    private function hashSignature(): string
    {
        return (new Hash(
            $this->signature,
        ))->get();
    }
}
