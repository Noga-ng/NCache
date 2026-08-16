<?php

declare(strict_types=1);

namespace NCache\Psr\PsrCache;

use DateInterval;
use DateTimeImmutable;
use DateTimeInterface;
use Psr\Cache\CacheItemInterface;

final class PsrCacheItem implements CacheItemInterface
{
    private ?DateTimeInterface $expiration = null;

    public function __construct(
        private readonly string $key,
        private mixed $value = null,
        private bool $hit = false,
    ) {
    }

    public function getKey(): string
    {
        return $this->key;
    }

    public function get(): mixed
    {
        return $this->value;
    }

    public function isHit(): bool
    {
        if (!$this->hit) {
            return false;
        }

        if ($this->expiration === null) {
            return true;
        }

        return $this->expiration->getTimestamp() > time();
    }

    public function set(mixed $value): static
    {
        $this->value = $value;

        return $this;
    }

    public function expiresAt(?DateTimeInterface $expiration): static
    {
        $this->expiration = $expiration;

        return $this;
    }

    public function expiresAfter(int|DateInterval|null $time): static
    {
        if ($time === null) {
            $this->expiration = null;

            return $this;
        }

        $now = new DateTimeImmutable();

        $this->expiration = \is_int($time)
            ? $now->modify("+{$time} seconds")
            : $now->add($time);

        return $this;
    }

    /*
     * Method interne NCache.
     */
    public function expiration(): ?DateTimeInterface
    {
        return $this->expiration;
    }
}
