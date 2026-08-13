<?php

declare(strict_types=1);

namespace NCache\Config\Connection;

use Redis;
use RedisException;
use RuntimeException;

final class RedisConn
{
    private ?Redis $redis = null;

    public function __construct(
        private readonly string $host = '127.0.0.1',
        private readonly int $port = 6379,
        private readonly int|float $timeout = 5.0,
        private readonly ?string $password = null,
        private readonly int $database = 0,
    ) {
    }

    public function connect(): Redis
    {
        if ($this->redis !== null) {
            return $this->redis;
        }

        try {
            $redis = new Redis();

            $redis->connect(
                $this->host,
                $this->port,
                $this->timeout,
            );

            if ($this->password !== null) {
                $redis->auth($this->password);
            }

            if ($this->database !== 0) {
                if (!$redis->select($this->database)) {
                    throw new RuntimeException(
                        "Unable to select Redis database {$this->database}.",
                    );
                }
            }

            $this->redis = $redis;

            return $this->redis;
        } catch (RedisException $exception) {
            $this->redis = null;

            throw new RuntimeException(
                'Failed to connect to Redis.',
                previous: $exception,
            );
        }
    }

    public function isConnected(): bool
    {
        return $this->redis !== null;
    }

    public function disconnect(): void
    {
        if ($this->redis === null) {
            return;
        }

        try {
            $this->redis->close();
        } finally {
            $this->redis = null;
        }
    }
}
