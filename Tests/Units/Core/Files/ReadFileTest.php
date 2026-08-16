<?php

declare(strict_types=1);

namespace NCache\Tests\Units\Core\Files;

use NCache\Core\Files\ReadFile;
use NCache\Enum\CType;
use NCache\Exceptions\FailedReadCacheException;
use NCache\Tests\TestsUnit\TestsUnit;

final class ReadFileTest extends TestsUnit
{
    protected function setUp(): void
    {
        $this->directory('ncache-read-');
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->directory);
        parent::tearDown();
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
            (new ReadFile($file, CType::STRING))->get(),
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
            json_encode($data, JSON_THROW_ON_ERROR),
        );

        self::assertSame(
            $data,
            (new ReadFile($file, CType::JSON))->get(),
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
            serialize($data),
        );

        self::assertSame(
            $data,
            (new ReadFile($file, CType::SERIALIZE))->get(),
        );
    }

    public function testReadArrayPHP(): void
    {
        $data = [
            'a' => 1,
            'b' => true,
        ];

        $content = '<?php '
            . PHP_EOL
            . ' return '
            . \var_export($data, true)
            . ';';

        $file = $this->createFile(
            'cache.php',
            $content,
        );

        self::assertSame(
            $data,
            (new ReadFile($file, CType::ARRAY_PHP))->get(),
        );
    }

    public function testReadEmptyString(): void
    {
        $file = $this->createFile('empty.txt', '');

        self::assertSame(
            '',
            (new ReadFile($file, CType::STRING))->get(),
        );
    }



    public function testInvalidJsonThrowsException(): void
    {
        $file = $this->createFile(
            'invalid.json',
            '{"name":}',
        );

        $this->expectException(
            FailedReadCacheException::class,
        );

        (new ReadFile($file, CType::JSON))->get();
    }

    public function testJsonMustContainArray(): void
    {
        $file = $this->createFile(
            'number.json',
            '123',
        );

        $this->expectException(
            FailedReadCacheException::class,
        );

        (new ReadFile($file, CType::JSON))->get();
    }

    public function testSerializedDataMustContainArray(): void
    {
        $file = $this->createFile(
            'serialized.cache',
            serialize('hello'),
        );

        $this->expectException(
            FailedReadCacheException::class,
        );

        (new ReadFile($file, CType::SERIALIZE))->get();
    }

    public function testCorruptedSerializedDataThrowsException(): void
    {
        $file = $this->createFile(
            'corrupted.cache',
            'not-a-serialized-value',
        );

        $this->expectException(
            FailedReadCacheException::class,
        );

        (new ReadFile($file, CType::SERIALIZE))->get();
    }

    public function testArrayPhpMustReturnArray(): void
    {
        $file = $this->createFile(
            'invalid.php',
            '<?php return "hello";',
        );

        $this->expectException(
            FailedReadCacheException::class,
        );

        (new ReadFile(
            $file,
            CType::ARRAY_PHP,
        ))->get();
    }

    public function testArrayPhpRejectsNonPhpContent(): void
    {
        $file = $this->createFile(
            'fake.php',
            'this is not php',
        );

        $this->expectException(
            FailedReadCacheException::class,
        );

        (new ReadFile(
            $file,
            CType::ARRAY_PHP,
        ))->get();
    }

    public function testArrayPhpAcceptsPhpContentRegardlessOfExtension(): void
    {
        $data = [
            'id' => 42,
        ];

        $file = $this->createFile(
            'cache.txt',
            '<?php return '
                . var_export($data, true)
                . ';',
        );

        self::assertSame(
            $data,
            (new ReadFile(
                $file,
                CType::ARRAY_PHP,
            ))->get(),
        );
    }

    public function testInvalidPhpThrowsReadException(): void
    {
        $file = $this->createFile(
            'broken.php',
            '<?php return [;',
        );

        $this->expectException(
            FailedReadCacheException::class,
        );

        (new ReadFile(
            $file,
            CType::ARRAY_PHP,
        ))->get();
    }

    public function testLargeArrayPhpCanBeRead(): void
    {
        $data = [];

        for ($i = 0; $i < 5000; $i++) {
            $data[] = [
                'id' => $i,
                'name' => "user-{$i}",
            ];
        }

        $content = '<?php return '
            . var_export($data, true)
            . ';';

        $file = $this->createFile(
            'large.php',
            $content,
        );

        self::assertSame(
            $data,
            (new ReadFile(
                $file,
                CType::ARRAY_PHP,
            ))->get(),
        );
    }

    public function testLargeFileCanBeRead(): void
    {
        $content = str_repeat('abcdef', 25000);

        $file = $this->createFile(
            'large.txt',
            $content,
        );

        self::assertSame(
            $content,
            (new ReadFile($file, CType::STRING))->get(),
        );
    }

    public function testMultipleReadsReturnSameContent(): void
    {
        $file = $this->createFile(
            'cache.txt',
            'NCache',
        );

        $reader = new ReadFile(
            $file,
            CType::STRING,
        );

        self::assertSame('NCache', $reader->get());
        self::assertSame('NCache', $reader->get());
        self::assertSame('NCache', $reader->get());
    }
}
