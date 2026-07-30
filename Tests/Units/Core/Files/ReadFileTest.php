<?php
declare(strict_types=1);

namespace NCache\Tests\Units\Core\Files;

use NCache\Core\Files\ReadFile;
use NCache\Enum\CType;
use NCache\Exceptions\FailedReadCacheException;
use PHPUnit\Framework\TestCase;

final class ReadFileTest extends TestCase
{
    private string $directory;

    protected function setUp(): void
    {
        $this->directory = sys_get_temp_dir()
            . DIRECTORY_SEPARATOR
            . 'ncache-read-'
            . bin2hex(random_bytes(8));

        mkdir($this->directory, 0777, true);
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->directory);
    }

    public function testReadString(): void
    {
        $file = $this->createFile('cache.txt', 'hello');

        $reader = new ReadFile($file, CType::STRING);

        self::assertSame('hello', $reader->get());
    }

    public function testReadUtf8String(): void
    {
        $content = 'Bonjour Madagascar 🇲🇬';

        $file = $this->createFile('utf8.txt', $content);

        self::assertSame(
            $content,
            (new ReadFile($file, CType::STRING))->get()
        );
    }

    public function testReadJsonArray(): void
    {
        $data = [
            'name' => 'Noga',
            'age' => 24,
        ];

        $file = $this->createFile(
            'cache.json',
            json_encode($data, JSON_THROW_ON_ERROR)
        );

        self::assertSame(
            $data,
            (new ReadFile($file, CType::JSON))->get()
        );
    }

    public function testReadSerializedArray(): void
    {
        $data = [
            'a' => 1,
            'b' => true,
        ];

        $file = $this->createFile(
            'cache.dat',
            serialize($data)
        );

        self::assertSame(
            $data,
            (new ReadFile($file, CType::SERIALIZE))->get()
        );
    }

    public function testReadEmptyString(): void
    {
        $file = $this->createFile('empty.txt', '');

        self::assertSame(
            '',
            (new ReadFile($file, CType::STRING))->get()
        );
    }

    public function testMissingFileThrowsException(): void
    {
        $this->expectException(
            FailedReadCacheException::class
        );

        new ReadFile(
            $this->directory . '/missing.txt',
            CType::STRING
        );
    }

    public function testInvalidJsonThrowsException(): void
    {
        $file = $this->createFile(
            'invalid.json',
            '{"name":}'
        );

        $this->expectException(
            FailedReadCacheException::class
        );

        (new ReadFile($file, CType::JSON))->get();
    }

    public function testJsonMustContainArray(): void
    {
        $file = $this->createFile(
            'number.json',
            '123'
        );

        $this->expectException(
            FailedReadCacheException::class
        );

        (new ReadFile($file, CType::JSON))->get();
    }

    public function testSerializedDataMustContainArray(): void
    {
        $file = $this->createFile(
            'serialized.cache',
            serialize('hello')
        );

        $this->expectException(
            FailedReadCacheException::class
        );

        (new ReadFile($file, CType::SERIALIZE))->get();
    }

    public function testCorruptedSerializedDataThrowsException(): void
    {
        $file = $this->createFile(
            'corrupted.cache',
            'not-a-serialized-value'
        );

        $this->expectException(
            FailedReadCacheException::class
        );

        (new ReadFile($file, CType::SERIALIZE))->get();
    }

    public function testLargeFileCanBeRead(): void
    {
        $content = str_repeat('abcdef', 25000);

        $file = $this->createFile(
            'large.txt',
            $content
        );

        self::assertSame(
            $content,
            (new ReadFile($file, CType::STRING))->get()
        );
    }

    public function testMultipleReadsReturnSameContent(): void
    {
        $file = $this->createFile(
            'cache.txt',
            'NCache'
        );

        $reader = new ReadFile(
            $file,
            CType::STRING
        );

        self::assertSame('NCache', $reader->get());
        self::assertSame('NCache', $reader->get());
        self::assertSame('NCache', $reader->get());
    }

    private function createFile(
        string $name,
        string $content
    ): string {
        $file = $this->directory
            . DIRECTORY_SEPARATOR
            . $name;

        file_put_contents($file, $content);

        return $file;
    }

    private function removeDirectory(string $directory): void
    {
        if (!is_dir($directory)) {
            return;
        }

        foreach (scandir($directory) ?: [] as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $path = $directory . DIRECTORY_SEPARATOR . $item;

            if (is_dir($path)) {
                $this->removeDirectory($path);
            } else {
                @unlink($path);
            }
        }

        @rmdir($directory);
    }
}