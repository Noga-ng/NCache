<?php

declare(strict_types=1);

namespace NCache\Tests\Units\Config\Connection;

use Memcached;
use NCache\Config\Connection\MCached;
use PHPUnit\Framework\TestCase;

final class MCachedTest extends TestCase
{
    private const HOST = '127.0.0.1';
    private const PORT = 11211;

    public function testConnectReturnsMemcachedInstance(): void
    {
        $connection = new MCached(
            self::HOST,
            self::PORT,
        );

        self::assertInstanceOf(
            Memcached::class,
            $connection->connect(),
        );
    }

    public function testConnectReturnsSameInstance(): void
    {
        $connection = new MCached(
            self::HOST,
            self::PORT,
        );

        $first = $connection->connect();
        $second = $connection->connect();

        self::assertSame(
            $first,
            $second,
        );
    }

    public function testIsConnectedReturnsFalseBeforeConnect(): void
    {
        $connection = new MCached(
            self::HOST,
            self::PORT,
        );

        self::assertFalse(
            $connection->isConnected(),
        );
    }

    public function testIsConnectedReturnsTrueWhenServerIsAvailable(): void
    {
        $connection = new MCached(
            self::HOST,
            self::PORT,
        );

        $connection->connect();

        self::assertTrue(
            $connection->isConnected(),
        );
    }

    public function testDisconnectResetsConnection(): void
    {
        $connection = new MCached(
            self::HOST,
            self::PORT,
        );

        $connection->connect();

        self::assertTrue(
            $connection->isConnected(),
        );

        $connection->disconnect();

        self::assertFalse(
            $connection->isConnected(),
        );
    }

    public function testConfiguredServerIsRegistered(): void
    {
        $connection = new MCached(
            self::HOST,
            self::PORT,
        );

        $client = $connection->connect();

        $servers = $client->getServerList();

        self::assertCount(1, $servers);

        self::assertSame(
            self::HOST,
            $servers[0]['host'],
        );

        self::assertSame(
            self::PORT,
            $servers[0]['port'],
        );
    }

    public function testConnectionCanWriteAndRead(): void
    {
        $client = (new MCached(
            self::HOST,
            self::PORT,
        ))->connect();

        $key = 'ncache_connection_test';

        self::assertTrue(
            $client->set(
                $key,
                ['working' => true],
                30,
            ),
        );

        self::assertSame(
            ['working' => true],
            $client->get($key),
        );

        $client->delete($key);
    }

    public function testResultCodeAndMessageAreAccessible(): void
    {
        $connection = new MCached(
            self::HOST,
            self::PORT,
        );

        $client = $connection->connect();

        $client->get(
            'ncache_missing_connection_test',
        );

        self::assertSame(
            Memcached::RES_NOTFOUND,
            $connection->resultCode(),
        );

        self::assertNotSame(
            '',
            $connection->resultMessage(),
        );
    }
}
