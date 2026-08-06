<?php declare(strict_types=1);

namespace NCache\Core\TtlManager;

use NCache\Contract\Clock;
use NCache\Enum\TtlState;

final class ExpirationAnalyzer
{
    public function __construct(
        private readonly Clock $clock
    ) {}

    public function state(?int $expiresAt): TtlState
    {
        if ($expiresAt === null) {
            return TtlState::PERSISTENT;
        }

        return $this->clock->now() >= $expiresAt
            ? TtlState::EXPIRED
            : TtlState::FRESH;
    }

    public function remaining(?int $expiresAt): ?int
    {
        if ($expiresAt === null) {
            return null;
        }

        return max(
            0,
            $expiresAt - $this->clock->now()
        );
    }
}
