<?php declare(strict_types=1);

namespace NCache\Core\Clock;

use NCache\Contract\Clock;
use InvalidArgumentException;

final class FrozenClock implements Clock
{
    private int $now;

    public function __construct(?int $now = null)
    {
        $this->now = $now ?? time();
    }

    public function now(): int
    {
        return $this->now;
    }

    public function advance(int $seconds): int
    {
        if ($seconds < 0) {
            throw new InvalidArgumentException(
                'Seconds must be greater than or equal to zero.'
            );
        }

        $this->now += $seconds;

        return $this->now;
    }

    public function rewind(int $seconds): int
    {
        if ($seconds < 0) {
            throw new InvalidArgumentException(
                'Seconds must be greater than or equal to zero.'
            );
        }

        $this->now -= $seconds;

        return $this->now;
    }
}
