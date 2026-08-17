<?php

declare(strict_types=1);

namespace NCache\Core;

use NCache\Exceptions\InvalidCacheArgumentException;

final class Hash
{
    private string $values = '';

    /**
     * @param array<array-key,mixed>|string|int|bool|float|null|object $data
     * @param string|bool|null $algo
     */
    public function __construct(
        private readonly mixed $data,
        private readonly mixed $algo = null,
    ) {
        if ($this->data === null) {
            throw new InvalidCacheArgumentException(
                'cannot hash a value null',
            );
        }

        $this->normalize();
    }

    public function normalize(): void
    {
        $this->values =  match (true) {
            \is_array($this->data) => serialize($this->data),
            \is_object($this->data) => \serialize($this->data),
            default => (string)$this->data
        };
    }

    public function get(): string
    {
        if ($this->algo !== null && !\is_string($this->algo)) {
            throw new InvalidCacheArgumentException(
                'algo must of type string, '
                .\gettype($this->algo).' given ',
            );
        }
        $algo = $this->algo !== null ? $this->algo : 'xxh128';

        return hash($algo, $this->values);
    }

    public function toMd5(): string
    {

        if ($this->algo !== null && !\is_bool($this->algo)) {
            throw new InvalidCacheArgumentException(
                'algo must of type boolean, '
                .\gettype($this->algo).' given ',
            );
        }

        $bin = $this->algo !== null ? $this->algo : false;

        return \md5($this->values, $bin);
    }

}
