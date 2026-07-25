<?php
declare(strict_types=1);

namespace NCache\Config\Ttl;

final class Expiration
{
    public function __construct(
        private readonly ?int $ttl,
        private readonly ?int $expiredAt
    ) {}

    public static function fromTTL(?int $ttl): self
    {
        return new self(
            $ttl,
            $ttl !== null ? time() + $ttl : null
        );
    }

    public function ttl(): ?int
    {
        return $this->ttl;
    }

    public function timestamp(): ?int
    {
        return $this->expiredAt;
    }

    public function expired(): bool
    {
        return $this->expiredAt !== null
            && time() >= $this->expiredAt;
    }
}