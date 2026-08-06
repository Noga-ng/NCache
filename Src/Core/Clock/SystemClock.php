<?php declare(strict_types=1);

namespace NCache\Core\Clock;

use NCache\Contract\Clock;

final class SystemClock implements Clock
{
    public function now(): int
    {
        return time();
    }
}
