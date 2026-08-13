<?php

declare(strict_types=1);

namespace NCache\Core;

use NCache\Exceptions\InvalidCacheArgumentException;

final class Hash
{
    /**
     * @param array<array-key,mixed>|string|int|bool|float|null $data
     */
    public function __construct(
        private readonly mixed $data,
        private readonly string $algo = 'xxh128',
    ) {
        if ($this->data === null) {
            throw new InvalidCacheArgumentException(
                'cannot hash a value null',
            );
        }
    }


    public function get(): string
    {
        return match (true) {
            \is_array($this->data) => hash(
                $this->algo,
                serialize($this->data),
            ),
            default => hash(
                $this->algo,
                (string) $this->data,
            )
        };
    }

}
