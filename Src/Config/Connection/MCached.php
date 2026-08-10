<?php
declare(strict_types=1);

namespace NCache\Config\Connection;

use Memcached;
use RuntimeException;

final class MCached
{
    private ?Memcached $connection = null;

    public function __construct(
        private readonly string $host = '127.0.0.1',
        private readonly int $port = 11211,
        private readonly int $weight = 0,
    ) {}

    public function connect(): Memcached
    {
        if ($this->connection !== null) {
            return $this->connection;
        }

        $memcached = new Memcached();

        if (!$memcached->addServer(
            $this->host,
            $this->port,
            $this->weight
        )) {
            throw new RuntimeException(
                'Unable to configure Memcached server.'
            );
        }

        /*
         * addServer() ne garantit pas que le serveur
         * est réellement joignable.
         *
         * La validation réseau peut être faite séparément.
         */

        $this->connection = $memcached;

        return $this->connection;
    }

    public function isConnected(): bool
    {
        if ($this->connection === null) {
            return false;
        }

        $stats = $this->connection->getStats();

        return $stats !== [];
    }

    public function disconnect(): void
    {
        if ($this->connection !== null) {
            $this->connection->quit();
        }

        $this->connection = null;
    }

    public function resultCode(): int
    {
        return $this->connect()->getResultCode();
    }

    public function resultMessage(): string
    {
        return $this->connect()->getResultMessage();
    }
}