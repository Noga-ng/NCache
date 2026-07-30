<?php

declare(strict_types=1);

namespace NCache\Tests\Units\Core;

use NCache\Core\CacheItem\CacheItem;
use NCache\Core\CachePath;
use NCache\Core\Hash;
use NCache\Enum\CType;
use PHPUnit\Framework\TestCase;

final class CacheItemTest extends TestCase
{
    private string $basePath;

    protected function setUp(): void
    {
        $this->basePath = sys_get_temp_dir()
            . DIRECTORY_SEPARATOR
            . 'ncache-item-tests-'
            . uniqid('', true);
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->basePath);
    }

    public function testItReturnsTheOriginalKey(): void
    {
        $item = $this->createItem('users');

        self::assertSame('users', $item->key());
    }

    public function testItReturnsTheHashedKey(): void
    {
        $item = $this->createItem('users');

        self::assertSame(
            (new Hash('users'))->get(),
            $item->hashedKey()
        );
    }

    public function testItReturnsTheConfiguredType(): void
    {
        $item = $this->createItem('users', CType::JSON);

        self::assertSame(CType::JSON, $item->type());
        self::assertSame('JSON', $item->typeName());
    }

    public function testDataIsEmptyInitially(): void
    {
        $item = $this->createItem('users');

        self::assertSame([], $item->getData());
    }

    public function testSetDataAddsData(): void
    {
        $item = $this->createItem('users');

        $item->setData(
            ['id' => 1, 'name' => 'Noga'],
        );

        self::assertSame(
            [
                ['id' => 1, 'name' => 'Noga'],
            ],
            $item->getData()
        );
    }

    public function testSetDataAccumulatesValues(): void
    {
        $item = $this->createItem('users');

        $item->setData(
            ['id' => 1, 'name' => 'Noga'],
        );

        $item->setData(
            ['id' => 2, 'name' => 'Germainio'],
        );

        self::assertSame(
            [
                ['id' => 1, 'name' => 'Noga'],
                ['id' => 2, 'name' => 'Germainio'],
            ],
            $item->getData()
        );
    }

    public function testSignatureIsNullInitially(): void
    {
        $item = $this->createItem('users');

        self::assertNull($item->getSignature());
    }

    public function testSetSignatureGeneratesSha256Hash(): void
    {
        $item = $this->createItem('users');

        $signatureData = [
            'updated_at' => '2026-07-28',
            'version' => 1,
        ];

        $item->setSignature($signatureData);

        self::assertSame(
            hash('sha256', serialize($signatureData)),
            $item->getSignature()
        );
    }

    public function testTtlIsNullInitially(): void
    {
        $item = $this->createItem('users');

        self::assertNull($item->ttlValue());
        self::assertNull($item->expiredAt());
    }

    public function testSetTtlDefinesExpirationInformation(): void
    {
        $item = $this->createItem('users');

        $before = time();

        $item->setTtl(3600);

        $after = time();

        self::assertSame(3600, $item->ttlValue());
        self::assertNotNull($item->expiredAt());
        self::assertGreaterThanOrEqual(
            $before + 3600,
            $item->expiredAt()
        );
        self::assertLessThanOrEqual(
            $after + 3600,
            $item->expiredAt()
        );
    }

    public function testSetDirChangesTheCachePath(): void
    {
        $item = $this->createItem('users');

        $item->setDir('api');

        self::assertSame(
            $this->basePath
            . DIRECTORY_SEPARATOR
            . 'api',
            $item->path()
        );
    }

    public function testFileReturnsHashedFilePath(): void
    {
        $item = $this->createItem('users', CType::JSON);

        self::assertSame(
            $this->basePath
            . DIRECTORY_SEPARATOR
            . (new Hash('users'))->get(),
            $item->file()
        );
    }

    public function testRedisFileIsNull(): void
    {
        $item = $this->createItem('users', CType::REDIS);

        self::assertNull($item->file());
    }

    public function testSQLiteFileReturnsTheDirectoryPath(): void
    {
        $item = $this->createItem('users', CType::SQLite);

        self::assertSame(
            $this->basePath,
            $item->file()
        );
    }

    public function testToArrayReturnsTheCompleteItemStructure(): void
    {
        $item = $this->createItem('users', CType::JSON);

        $item->setSignature('users-version-1');
        $item->setData(
            ['id' => 1, 'name' => 'Noga'],
        );
        $item->setTtl(3600);

        $result = $item->toArray();

        self::assertSame('JSON', $result['type']);
        self::assertSame('users', $result['name']);
        self::assertSame(
            (new Hash('users'))->get(),
            $result['key']
        );
        self::assertSame(
            hash('sha256', 'users-version-1'),
            $result['signature']
        );
        self::assertSame(3600, $result['ttl']);
        self::assertIsInt($result['expiresAt']);
        self::assertSame(
            [
                ['id' => 1, 'name' => 'Noga'],
            ],
            $result['data']
        );
    }

    private function createItem(
        string $key,
        CType $type = CType::JSON
    ): CacheItem {
        return new CacheItem(
            $key,
            $type,
            new CachePath($this->basePath)
        );
    }

    private function removeDirectory(string $directory): void
    {
        if (!is_dir($directory)) {
            return;
        }

        $items = scandir($directory);

        if ($items === false) {
            return;
        }

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $path = $directory . DIRECTORY_SEPARATOR . $item;

            if (is_dir($path)) {
                $this->removeDirectory($path);
                continue;
            }

            unlink($path);
        }

        rmdir($directory);
    }
}