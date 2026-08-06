<?php declare(strict_types=1);

namespace NCache\Core\TtlManager;

use NCache\Contract\Clock;
use NCache\Enum\TtlState;
use InvalidArgumentException;

final class Expiration
{
    private readonly ExpirationAnalyzer $analyzer;

    /**
     * @param int|null $ttl
     * @param int|null $expiresAt
     * @param Clock $clock
     * @throws InvalidArgumentException
     */
    public function __construct(
        private readonly ?int $ttl,
        private readonly ?int $expiresAt,
        private readonly Clock $clock
    ) {
        if (($ttl === null) !== ($expiresAt === null)) {
            throw new InvalidArgumentException(
                'TTL and expiresAt must both be null or both defined.'
            );
        }

        $this->analyzer = new ExpirationAnalyzer($clock);
    }

    /**
     * @param int|null $ttl
     * @param Clock $clock
     * @throws InvalidArgumentException
     * @return self
     */
    public static function fromTTL(?int $ttl, Clock $clock): self
    {

        return new self(
            $ttl,
            $ttl !== null
                ? $clock->now() + $ttl
                : null,
            $clock
        );
    }

    /**
     * @param int|null $ttl
     * @param int|null $expiresAt
     * @param Clock $clock
     * @return self
     */
    public static function restore(?int $ttl, ?int $expiresAt, Clock $clock): self
    {
        return new self(
            $ttl,
            $expiresAt,
            $clock
        );
    }

    public function isExpired(): bool
    {
        return $this->analyzer->state(
            $this->expiresAt
        ) === TtlState::EXPIRED;
    }

    public function remaining(): ?int
    {
        return $this->analyzer->remaining(
            $this->expiresAt
        );
    }

    public function state():string{
        return $this->analyzer->state(
            $this->expiresAt
            )->name;
    }

    /**
     * @return int|null
     */
    public function ttl(): ?int
    {
        return $this->ttl;
    }

    /**
     * @return int|null
     */
    public function timestamp(): ?int
    {
        return $this->expiresAt;
    }
}
