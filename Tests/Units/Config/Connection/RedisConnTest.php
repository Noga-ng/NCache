<?php

declare(strict_types=1);

namespace NCache\Tests\Units\Config\Connection;

use NCache\Config\Connection\RedisConn;
use PHPUnit\Framework\TestCase;
use Redis;

final class RedisConnTest extends TestCase
{
    private const HOST = '127.0.0.1';
    private const PORT = 6379;

    public function testConnectReturnsRedisInstance(): void
    {
        $connection = new RedisConn(
            self::HOST,
            self::PORT
        );

        self::assertInstanceOf(
            Redis::class,
            $connection->connect()
        );
    }

    public function testConnectReturnsSameInstance(): void
    {
        $connection = new RedisConn(
            self::HOST,
            self::PORT
        );

        self::assertSame(
            $connection->connect(),
            $connection->connect()
        );
    }

    public function testConnectionCanPingServer(): void
    {
        $redis = (new RedisConn(
            self::HOST,
            self::PORT
        ))->connect();

        self::assertTrue(
            $redis->ping()
        );
    }

    public function testConnectionCanWriteAndRead(): void
    {
        $redis = (new RedisConn(
            self::HOST,
            self::PORT
        ))->connect();

        $key = 'ncache:test:redis-connection';

        self::assertTrue(
            $redis->set(
                $key,
                'working'
            )
        );

        self::assertSame(
            'working',
            $redis->get($key)
        );

        $redis->del($key);
    }

    public function testConnectionCanDeleteValue(): void
    {
        $redis = (new RedisConn(
            self::HOST,
            self::PORT
        ))->connect();

        $key = 'ncache:test:redis-delete';

        self::assertTrue(
            $redis->set($key, 'value')
        );

        self::assertSame(
            1,
            $redis->del($key)
        );

        self::assertSame(
            0,
            $redis->exists($key)
        );
    }

    public function testIsConnectedReturnsFalseBeforeConnect(): void
    {
        $connection = new RedisConn(
            self::HOST,
            self::PORT
        );

        self::assertFalse(
            $connection->isConnected()
        );
    }

    public function testIsConnectedReturnsTrueAfterConnect(): void
    {
        $connection = new RedisConn(
            self::HOST,
            self::PORT
        );

        $connection->connect();

        self::assertTrue(
            $connection->isConnected()
        );
    }

    public function testDisconnectResetsConnectionState(): void
    {
        $connection = new RedisConn(
            self::HOST,
            self::PORT
        );

        $connection->connect();

        self::assertTrue(
            $connection->isConnected()
        );

        $connection->disconnect();

        self::assertFalse(
            $connection->isConnected()
        );
    }
}
