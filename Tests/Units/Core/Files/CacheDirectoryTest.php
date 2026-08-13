<?php

declare(strict_types=1);

namespace NCache\Tests\Units\Core\Files;

use NCache\Exceptions\InvalidCacheArgumentException;
use NCache\Tests\TestsUnit\TestsUnit;
use SplFileInfo;

final class CacheDirectoryTest extends TestsUnit
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->directory(
            'ncache-directory-'
        );
    }

    protected function tearDown(): void
    {
        $this->removeDirectory(
            $this->directory
        );

        parent::tearDown();
    }

    public function testEmptyDirectoryReturnsEmptyList(): void
    {
        $directory = $this->cacheDirectory(
            $this->directory
        );

        $results = $this->withoutConfigFile(
            $directory->iterate()
        );

        self::assertSame(
            [],
            $results
        );
    }

    public function testIterateReturnsSplFileInfoInstances(): void
    {
        $this->createFile(
            'first.json'
        );

        $this->createFile(
            'second.nc'
        );

        $results = $this->withoutConfigFile(
            $this
                ->cacheDirectory(
                    $this->directory
                )
                ->iterate()
        );

        self::assertCount(
            2,
            $results
        );

        foreach ($results as $result) {
            self::assertInstanceOf(
                SplFileInfo::class,
                $result
            );
        }
    }

    public function testIterateReturnsFilesFromDirectory(): void
    {
        $first = $this->createFile(
            'first.json'
        );

        $second = $this->createFile(
            'second.nc'
        );

        $results = $this->withoutConfigFile(
            $this
                ->cacheDirectory(
                    $this->directory
                )
                ->iterate()
        );

        self::assertSame(
            $this->sortPaths([
                $first,
                $second,
            ]),
            $this->extractPaths(
                $results
            )
        );
    }

    public function testIterateTraversesNestedDirectories(): void
    {
        $rootFile = $this->createFile(
            'root.json'
        );

        $nestedFile = $this->createFile(
            'nested/cache.nc'
        );

        $deepFile = $this->createFile(
            'nested/deep/cache.txt'
        );

        $results = $this->withoutConfigFile(
            $this
                ->cacheDirectory(
                    $this->directory
                )
                ->iterate()
        );

        $paths = $this->extractPaths(
            $results
        );

        self::assertContains(
            $this->normalizePath(
                $rootFile
            ),
            $paths
        );

        self::assertContains(
            $this->normalizePath(
                $nestedFile
            ),
            $paths
        );

        self::assertContains(
            $this->normalizePath(
                $deepFile
            ),
            $paths
        );
    }

    public function testIterateIncludesNestedDirectories(): void
    {
        $nestedDirectory = $this->directory
            . DIRECTORY_SEPARATOR
            . 'nested';

        self::assertTrue(
            mkdir(
                $nestedDirectory,
                0o777,
                true
            )
        );

        $this->createFile(
            'nested/cache.json'
        );

        $results = $this->withoutConfigFile(
            $this
                ->cacheDirectory(
                    $this->directory
                )
                ->iterate()
        );

        $directories = array_filter(
            $results,
            static fn(SplFileInfo $file): bool
                => $file->isDir()
        );

        self::assertNotEmpty(
            $directories
        );

        self::assertContains(
            $this->normalizePath(
                $nestedDirectory
            ),
            $this->extractPaths(
                array_values(
                    $directories
                )
            )
        );
    }

    public function testIterateSupportsMultipleDirectories(): void
    {
        $secondDirectory = sys_get_temp_dir()
            . DIRECTORY_SEPARATOR
            . 'ncache-directory-second-'
            . bin2hex(
                random_bytes(8)
            );

        self::assertTrue(
            mkdir(
                $secondDirectory,
                0o777,
                true
            )
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
                    'cache'
                )
            );

            $results = $this->withoutConfigFile(
                $this
                    ->cacheDirectory([
                        $this->directory,
                        $secondDirectory,
                    ])
                    ->iterate()
            );

            self::assertSame(
                $this->sortPaths([
                    $firstFile,
                    $secondFile,
                ]),
                $this->extractPaths(
                    $results
                )
            );
        } finally {
            $this->removeDirectory(
                $secondDirectory
            );
        }
    }

    public function testInvalidDirectoryThrowsException(): void
    {
        $missingDirectory = $this->directory
            . DIRECTORY_SEPARATOR
            . 'missing';

        $this->expectException(
            InvalidCacheArgumentException::class
        );

        $this->expectExceptionMessage(
            "cannot find a directory on {$missingDirectory}"
        );

        $this
            ->cacheDirectory(
                $missingDirectory
            )
            ->iterate();
    }

    public function testRepeatedIterationDoesNotDuplicateResults(): void
    {
        $this->createFile(
            'first.json'
        );

        $this->createFile(
            'second.nc'
        );

        $directory = $this->cacheDirectory(
            $this->directory
        );

        $firstIteration = $this->withoutConfigFile(
            $directory->iterate()
        );

        $secondIteration = $this->withoutConfigFile(
            $directory->iterate()
        );

        self::assertSame(
            $this->extractPaths(
                $firstIteration
            ),
            $this->extractPaths(
                $secondIteration
            )
        );
    }

    /**
     * @param list<SplFileInfo> $files
     * @return list<SplFileInfo>
     */
    private function withoutConfigFile(
        array $files
    ): array {
        return array_values(
            array_filter(
                $files,
                static fn(SplFileInfo $file): bool
                    => $file->getFilename()
                        !== 'ncache.config.json'
            )
        );
    }

    /**
     * @param list<SplFileInfo> $files
     * @return list<string>
     */
    private function extractPaths(
        array $files
    ): array {
        $paths = array_map(
            fn(SplFileInfo $file): string
                => $this->normalizePath(
                    $file->getPathname()
                ),
            $files
        );

        sort(
            $paths
        );

        return array_values(
            $paths
        );
    }

    /**
     * @param list<string> $paths
     * @return list<string>
     */
    private function sortPaths(
        array $paths
    ): array {
        $paths = array_map(
            fn(string $path): string
                => $this->normalizePath(
                    $path
                ),
            $paths
        );

        sort(
            $paths
        );

        return array_values(
            $paths
        );
    }

    private function normalizePath(
        string $path
    ): string {
        return str_replace(
            [
                '/',
                '\\',
            ],
            DIRECTORY_SEPARATOR,
            $path
        );
    }
}
