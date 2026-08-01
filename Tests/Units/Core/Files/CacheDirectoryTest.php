<?php

declare(strict_types=1);

namespace NCache\Tests\Units\Core\Files;

use NCache\Core\Files\CacheDirectory;
use NCache\Exceptions\InvalidCacheArgumentException;
use PHPUnit\Framework\TestCase;
use SplFileInfo;

final class CacheDirectoryTest extends TestCase
{
    private string $baseDirectory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->baseDirectory = sys_get_temp_dir()
            . DIRECTORY_SEPARATOR
            . 'ncache-directory-'
            . bin2hex(random_bytes(8));

        self::assertTrue(
            mkdir($this->baseDirectory, 0777, true)
        );
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->baseDirectory);

        parent::tearDown();
    }

    public function testEmptyDirectoryReturnsEmptyList(): void
    {
        $directory = new CacheDirectory([
            $this->baseDirectory,
        ]);

        self::assertSame([], $directory->iterate());
    }

    public function testIterateReturnsSplFileInfoInstances(): void
    {
        $this->createFile('first.json');
        $this->createFile('second.nc');

        $results = (new CacheDirectory([
            $this->baseDirectory,
        ]))->iterate();

        self::assertCount(2, $results);

        foreach ($results as $result) {
            self::assertInstanceOf(
                SplFileInfo::class,
                $result
            );
        }
    }

    public function testIterateReturnsFilesFromDirectory(): void
    {
        $first = $this->createFile('first.json');
        $second = $this->createFile('second.nc');

        $results = (new CacheDirectory([
            $this->baseDirectory,
        ]))->iterate();

        self::assertSame(
            $this->sortPaths([$first, $second]),
            $this->extractPaths($results)
        );
    }

    public function testIterateTraversesNestedDirectories(): void
    {
        $rootFile = $this->createFile('root.json');

        $nestedFile = $this->createFile(
            'nested/cache.nc'
        );

        $deepFile = $this->createFile(
            'nested/deep/cache.txt'
        );

        $results = (new CacheDirectory([
            $this->baseDirectory,
        ]))->iterate();

        $paths = $this->extractPaths($results);

        self::assertContains(
            $this->normalizePath($rootFile),
            $paths
        );

        self::assertContains(
            $this->normalizePath($nestedFile),
            $paths
        );

        self::assertContains(
            $this->normalizePath($deepFile),
            $paths
        );
    }

    public function testIterateIncludesNestedDirectories(): void
    {
        $nestedDirectory = $this->baseDirectory
            . DIRECTORY_SEPARATOR
            . 'nested';

        self::assertTrue(
            mkdir($nestedDirectory, 0777, true)
        );

        $this->createFile('nested/cache.json');

        $results = (new CacheDirectory([
            $this->baseDirectory,
        ]))->iterate();

        $directories = array_filter(
            $results,
            static fn (SplFileInfo $file): bool =>
                $file->isDir()
        );

        self::assertNotEmpty($directories);

        self::assertContains(
            $this->normalizePath($nestedDirectory),
            $this->extractPaths(
                array_values($directories)
            )
        );
    }

    public function testIterateSupportsMultipleDirectories(): void
    {
        $secondDirectory = sys_get_temp_dir()
            . DIRECTORY_SEPARATOR
            . 'ncache-directory-second-'
            . bin2hex(random_bytes(8));

        self::assertTrue(
            mkdir($secondDirectory, 0777, true)
        );

        try {
            $firstFile = $this->createFile(
                'first.json'
            );

            $secondFile = $secondDirectory
                . DIRECTORY_SEPARATOR
                . 'second.nc';

            self::assertNotFalse(
                file_put_contents(
                    $secondFile,
                    'second'
                )
            );

            $results = (new CacheDirectory([
                $this->baseDirectory,
                $secondDirectory,
            ]))->iterate();

            self::assertSame(
                $this->sortPaths([
                    $firstFile,
                    $secondFile,
                ]),
                $this->extractPaths($results)
            );
        } finally {
            $this->removeDirectory(
                $secondDirectory
            );
        }
    }

    public function testInvalidDirectoryThrowsException(): void
    {
        $missingDirectory = $this->baseDirectory
            . DIRECTORY_SEPARATOR
            . 'missing';

        $this->expectException(
            InvalidCacheArgumentException::class
        );

        $this->expectExceptionMessage(
            "cannot find a directory on {$missingDirectory}"
        );

        (new CacheDirectory([
            $missingDirectory,
        ]))->iterate();
    }

    public function testRepeatedIterationDoesNotDuplicateResults(): void
    {
        $this->createFile('first.json');
        $this->createFile('second.nc');

        $directory = new CacheDirectory([
            $this->baseDirectory,
        ]);

        $firstIteration = $directory->iterate();
        $secondIteration = $directory->iterate();

        self::assertSame(
            $this->extractPaths($firstIteration),
            $this->extractPaths($secondIteration)
        );
    }

    private function createFile(
        string $relativePath,
        string $content = 'cache'
    ): string {
        $file = $this->baseDirectory
            . DIRECTORY_SEPARATOR
            . str_replace(
                ['/', '\\'],
                DIRECTORY_SEPARATOR,
                $relativePath
            );

        $directory = dirname($file);

        if (!is_dir($directory)) {
            self::assertTrue(
                mkdir($directory, 0777, true)
            );
        }

        self::assertNotFalse(
            file_put_contents($file, $content)
        );

        return $file;
    }

    /**
     * @param list<SplFileInfo> $files
     * @return list<string>
     */
    private function extractPaths(array $files): array
    {
        $paths = array_map(
            fn (SplFileInfo $file): string =>
                $this->normalizePath(
                    $file->getPathname()
                ),
            $files
        );

        sort($paths);

        return array_values($paths);
    }

    /**
     * @param list<string> $paths
     * @return list<string>
     */
    private function sortPaths(array $paths): array
    {
        $paths = array_map(
            fn (string $path): string =>
                $this->normalizePath($path),
            $paths
        );

        sort($paths);

        return array_values($paths);
    }

    private function normalizePath(
        string $path
    ): string {
        return str_replace(
            ['/', '\\'],
            DIRECTORY_SEPARATOR,
            $path
        );
    }

    private function removeDirectory(
        string $directory
    ): void {
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

            $path = $directory
                . DIRECTORY_SEPARATOR
                . $item;

            if (is_dir($path) && !is_link($path)) {
                $this->removeDirectory($path);
                continue;
            }

            @unlink($path);
        }

        @rmdir($directory);
    }
}