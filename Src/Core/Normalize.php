<?php

declare(strict_types=1);

namespace NCache\Core;

use Closure;
use NCache\Exceptions\InvalidCacheArgumentException;

/**
 * @phpstan-type ItemData array<array-key,mixed>|string|int|bool|float|null
 * @phpstan-type ItemCallBack ItemData|callable
 */
trait Normalize
{
    /**
     * @param null|string $key
     * @throws InvalidCacheArgumentException
     * @return void
     */
    private function obligatorKey(?string $key = null): void
    {
        if ($key === null || trim($key) === '') {
            throw new InvalidCacheArgumentException(
                'Key cannot be empty.',
            );
        }

        $this->validateKey($key);
    }

    /**
     * @template TResult
     *
     * @param callable():TResult $callBack
     *
     * @return TResult
     */
    private function itemCallback(callable $callBack): mixed
    {
        $call = $callBack();
        return match (true) {
            ($call instanceof Closure) => ($call()),
            \is_object($call) => \serialize($call),
            default => $call
        };
    }

    private function validateKey(string $key): void
    {
        if ($key === '') {
            throw new InvalidCacheArgumentException(
                'Cache key cannot be empty.',
            );
        }

        if (preg_match('/[{}()\/\\\\@:]/', $key) === 1) {
            throw new InvalidCacheArgumentException(
                "Invalid cache key: {$key}",
            );
        }
    }
}
