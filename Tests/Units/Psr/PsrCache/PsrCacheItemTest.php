<?php

declare(strict_types=1);

namespace NCache\Tests\Units\Psr\PsrCache;

use DateInterval;
use DateTimeImmutable;
use NCache\Psr\PsrCache\PsrCacheItem;
use PHPUnit\Framework\TestCase;

final class PsrCacheItemTest extends TestCase
{
    public function testGetKeyReturnsKey(): void
    {
        $item = new PsrCacheItem(
            'user.42',
        );

        self::assertSame(
            'user.42',
            $item->getKey(),
        );
    }

    public function testGetReturnsValue(): void
    {
        $item = new PsrCacheItem(
            'user.42',
            [
                'id' => 42,
            ],
        );

        self::assertSame(
            [
                'id' => 42,
            ],
            $item->get(),
        );
    }

    public function testIsHitReturnsConstructorState(): void
    {
        self::assertTrue(
            (new PsrCacheItem(
                'hit',
                'value',
                true,
            ))->isHit(),
        );

        self::assertFalse(
            (new PsrCacheItem(
                'miss',
                null,
                false,
            ))->isHit(),
        );
    }

    public function testSetReplacesValueAndReturnsSameInstance(): void
    {
        $item = new PsrCacheItem(
            'user.42',
        );

        $result = $item->set([
            'id' => 42,
        ]);

        self::assertSame(
            $item,
            $result,
        );

        self::assertSame(
            [
                'id' => 42,
            ],
            $item->get(),
        );
    }

    public function testExpiresAtStoresExpiration(): void
    {
        $expiration = new DateTimeImmutable(
            '+1 hour',
        );

        $item = new PsrCacheItem(
            'user.42',
        );

        self::assertSame(
            $item,
            $item->expiresAt(
                $expiration,
            ),
        );

        self::assertSame(
            $expiration,
            $item->expiration(),
        );
    }

    public function testExpiresAtNullRemovesExpiration(): void
    {
        $item = new PsrCacheItem(
            'user.42',
        );

        $item->expiresAt(
            new DateTimeImmutable(
                '+1 hour',
            ),
        );

        $item->expiresAt(
            null,
        );

        self::assertNull(
            $item->expiration(),
        );
    }

    public function testExpiresAfterIntegerCreatesExpiration(): void
    {
        $before = time();

        $item = new PsrCacheItem(
            'user.42',
        );

        $item->expiresAfter(
            3600,
        );

        $expiration = $item->expiration();

        self::assertNotNull(
            $expiration,
        );

        self::assertGreaterThanOrEqual(
            $before + 3599,
            $expiration->getTimestamp(),
        );

        self::assertLessThanOrEqual(
            time() + 3601,
            $expiration->getTimestamp(),
        );
    }

    public function testExpiresAfterDateIntervalCreatesExpiration(): void
    {
        $item = new PsrCacheItem(
            'user.42',
        );

        $before = time();

        $item->expiresAfter(
            new DateInterval(
                'PT1H',
            ),
        );

        $expiration = $item->expiration();

        self::assertNotNull(
            $expiration,
        );

        self::assertGreaterThanOrEqual(
            $before + 3599,
            $expiration->getTimestamp(),
        );
    }

    public function testExpiresAfterNullRemovesExpiration(): void
    {
        $item = new PsrCacheItem(
            'user.42',
        );

        $item->expiresAfter(
            3600,
        );

        $item->expiresAfter(
            null,
        );

        self::assertNull(
            $item->expiration(),
        );
    }
}
