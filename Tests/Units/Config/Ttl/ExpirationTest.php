<?php

declare(strict_types=1);

namespace NCache\Tests\Units\Config\Ttl;

use NCache\Config\Ttl\Expiration;
use PHPUnit\Framework\TestCase;

final class ExpirationTest extends TestCase
{
    public function testConstructorStoresTtl(): void
    {
        $expiration = new Expiration(3600, 2_000_000_000);

        self::assertSame(3600, $expiration->ttl());
    }

    public function testConstructorStoresExpirationTimestamp(): void
    {
        $expiration = new Expiration(3600, 2_000_000_000);

        self::assertSame(
            2_000_000_000,
            $expiration->timestamp()
        );
    }

    public function testItAcceptsNullTtlAndTimestamp(): void
    {
        $expiration = new Expiration(null, null);

        self::assertNull($expiration->ttl());
        self::assertNull($expiration->timestamp());
    }

    public function testFromTtlCreatesExpirationTimestamp(): void
    {
        $before = time();

        $expiration = Expiration::fromTTL(3600);

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
        $expiration = Expiration::fromTTL(null);

        self::assertNull($expiration->ttl());
        self::assertNull($expiration->timestamp());
        self::assertFalse($expiration->expired());
    }

    public function testFutureTimestampIsNotExpired(): void
    {
        $expiration = new Expiration(
            3600,
            time() + 3600
        );

        self::assertFalse($expiration->expired());
    }

    public function testPastTimestampIsExpired(): void
    {
        $expiration = new Expiration(
            3600,
            time() - 1
        );

        self::assertTrue($expiration->expired());
    }

    public function testCurrentTimestampIsExpired(): void
    {
        $expiration = new Expiration(
            0,
            time()
        );

        self::assertTrue($expiration->expired());
    }

    public function testZeroTtlExpiresImmediately(): void
    {
        $expiration = Expiration::fromTTL(0);

        self::assertSame(0, $expiration->ttl());
        self::assertTrue($expiration->expired());
    }

    public function testNegativeTtlIsAlreadyExpired(): void
    {
        $expiration = Expiration::fromTTL(-10);

        self::assertSame(-10, $expiration->ttl());
        self::assertTrue($expiration->expired());
    }
}