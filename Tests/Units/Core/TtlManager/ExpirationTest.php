<?php

declare(strict_types=1);

namespace NCache\Tests\Units\Core\TtlManager;

use NCache\Core\TtlManager\Expiration;
use NCache\Tests\TestsUnit\TestsUnit;

final class ExpirationTest extends TestsUnit
{
    public function testConstructorStoresTtl(): void
    {
        $expiration = $this->expiration(3600, 2_000_000_000);

        self::assertSame(3600, $expiration->ttl());
    }

    public function testConstructorStoresExpirationTimestamp(): void
    {
        $expiration = $this->expiration(3600, 2_000_000_000);

        self::assertSame(
            2_000_000_000,
            $expiration->timestamp()
        );
    }

    public function testItAcceptsNullTtlAndTimestamp(): void
    {
        $expiration = $this->expiration(null,null);

        self::assertNull($expiration->ttl());
        self::assertNull($expiration->timestamp());
    }

    public function testFromTtlCreatesExpirationTimestamp(): void
    {
        $before = time();

        $expiration = Expiration::fromTTL(3600,$this->clock());

        $after = time();

        self::assertSame(3600, $expiration->ttl());

        self::assertGreaterThanOrEqual(
            $before + 3600,
            $expiration->timestamp()
        );

        self::assertLessThanOrEqual(
            $after + 3600,
            $expiration->timestamp()
        );
    }

    public function testFromNullTtlCreatesNoExpiration(): void
    {
        $expiration = Expiration::fromTTL(null,$this->clock());

        self::assertNull($expiration->ttl());
        self::assertNull($expiration->timestamp());
        self::assertFalse($expiration->isExpired());
    }

    public function testFutureTimestampIsNotExpired(): void
    {
        $expiration = $this->expiration(3600,$this->clock()->now() + 3600);

        self::assertFalse($expiration->isExpired());
    }

    public function testPastTimestampIsExpired(): void
    {
        $expiration = $this->expiration(3600,$this->clock()->now() - 1);

        self::assertTrue($expiration->isExpired());
    }

    public function testCurrentTimestampIsExpired(): void
    {
        $expiration = $this->expiration(0,$this->clock()->now());

        self::assertTrue($expiration->isExpired());
    }

    public function testZeroTtlExpiresImmediately(): void
    {
        $expiration = Expiration::fromTTL(0,$this->clock());

        self::assertSame(0, $expiration->ttl());
        self::assertTrue($expiration->isExpired());
    }

    private function expiration(?int $ttl,?int $expiresAt):Expiration{
        return new Expiration(
            $ttl,
            $expiresAt,
            $this->clock()
        );
    }
}