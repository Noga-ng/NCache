<?php

declare(strict_types=1);

namespace NCache\Core\TtlManager;

use NCache\Contract\Clock;
use NCache\Core\CacheItem\CacheItem;
use NCache\Enum\TtlState;
use NCache\Registry\CacheRegistry;

final class TtlManager
{
    public function __construct(
        private readonly CacheItem $item,
        private readonly CacheRegistry $registry,
        private readonly Clock $clock
    ) {}

    public function preserveStoredExpiration(): void
    {
        $stored = $this->registry->get();
        if ($stored === null) {
            return;
        }

        if (
            !$this->item->ttlWasDefined()
            || $this->item->ttlValue() === $stored['ttl']
        ) {
            $this->item->restoreExpiration(
                $stored['ttl'],
                $stored['expiresAt'],
                $this->clock
            );
        }
    }

    public function isExpired(): bool
    {
        return $this->storedExpiration()?->isExpired()
            ?? false;
    }

    public function remaining(): ?int
    {
        return $this->storedExpiration()
                    ?->remaining();
    }

    public function state(): ?TtlState
    {
        return $this->storedExpiration()?->state();
    }

    private function storedExpiration(): ?Expiration
    {
        $stored = $this->registry->get();

        if ($stored === null) {
            return null;
        }

        return Expiration::restore(
            $stored['ttl'],
            $stored['expiresAt'],
            $this->clock
        );
    }
}
