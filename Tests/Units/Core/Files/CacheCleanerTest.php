<?php

declare(strict_types=1);

namespace NCache\Tests\Units\Core\Files;

use NCache\Tests\TestsUnit\TestsUnit;

final class CacheCleanerTest extends TestsUnit
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->directory(
            'ncache-cleaner-'
        );
    }

    protected function tearDown(): void
    {
        $this->removeDirectory(
            $this->directory
        );

        parent::tearDown();
    }

    public function testExtensionAllowedReturnsConstructorValue(): void
    {
        $cleaner = $this->cacheCleaner([
            'php',
            'json',
        ]);

        self::assertSame(
            [
                'php',
                'json',
            ],
            $cleaner->getExtensionAllowed()
        );
    }

    public function testDeleteExistingFile(): void
    {
        $file = $this->createFile(
            'cache.php'
        );

        $cleaner = $this->cacheCleaner(
            'php'
        );

        self::assertTrue(
            $cleaner->delete(
                $file
            )
        );

        self::assertFileDoesNotExist(
            $file
        );
    }

    public function testDeleteMissingFileReturnsTrue(): void
    {
        $cleaner = $this->cacheCleaner(
            'php'
        );

        self::assertTrue(
            $cleaner->delete(
                $this->directory
                    . DIRECTORY_SEPARATOR
                    . 'missing.php'
            )
        );
    }

    public function testClearDeletesOnlyAllowedExtensions(): void
    {
        $target = $this->fixtureDirectory(
            'clear-extensions'
        );

        $php = $target
            . DIRECTORY_SEPARATOR
            . 'a.php';

        $json = $target
            . DIRECTORY_SEPARATOR
            . 'b.json';

        $txt = $target
            . DIRECTORY_SEPARATOR
            . 'c.txt';

        self::assertNotFalse(
            file_put_contents(
                $php,
                'php'
            )
        );

        self::assertNotFalse(
            file_put_contents(
                $json,
                'json'
            )
        );

        self::assertNotFalse(
            file_put_contents(
                $txt,
                'txt'
            )
        );

        $cleaner = $this->cacheCleaner([
            'php',
            'json',
        ]);

        self::assertSame(
            2,
            $cleaner->clear(
                $target
            )
        );

        self::assertFileDoesNotExist(
            $php
        );

        self::assertFileDoesNotExist(
            $json
        );

        self::assertFileExists(
            $txt
        );

        self::assertFileExists(
            $this->configFile
        );
    }

    public function testClearDeletesFilesRecursively(): void
    {
        $target = $this->fixtureDirectory(
            'recursive'
        );

        $subDirectory = $target
            . DIRECTORY_SEPARATOR
            . 'sub';

        self::assertTrue(
            mkdir(
                $subDirectory,
                0o777,
                true
            )
        );

        $file = $subDirectory
            . DIRECTORY_SEPARATOR
            . 'cache.php';

        self::assertNotFalse(
            file_put_contents(
                $file,
                'cache'
            )
        );

        $cleaner = $this->cacheCleaner(
            'php'
        );

        self::assertSame(
            1,
            $cleaner->clear(
                $target
            )
        );

        self::assertFileDoesNotExist(
            $file
        );
    }

    public function testClearReturnsZeroWhenNothingMatches(): void
    {
        $target = $this->fixtureDirectory(
            'no-match'
        );

        $file = $target
            . DIRECTORY_SEPARATOR
            . 'cache.txt';

        self::assertNotFalse(
            file_put_contents(
                $file,
                'cache'
            )
        );

        $cleaner = $this->cacheCleaner(
            'php'
        );

        self::assertSame(
            0,
            $cleaner->clear(
                $target
            )
        );

        self::assertFileExists(
            $file
        );
    }

    public function testClearDoesNotDeleteDirectories(): void
    {
        $target = $this->fixtureDirectory(
            'keep-directory'
        );

        $cacheDirectory = $target
            . DIRECTORY_SEPARATOR
            . 'cache';

        self::assertTrue(
            mkdir(
                $cacheDirectory,
                0o777,
                true
            )
        );

        $file = $cacheDirectory
            . DIRECTORY_SEPARATOR
            . 'test.php';

        self::assertNotFalse(
            file_put_contents(
                $file,
                'cache'
            )
        );

        $cleaner = $this->cacheCleaner(
            'php'
        );

        self::assertSame(
            1,
            $cleaner->clear(
                $target
            )
        );

        self::assertFileDoesNotExist(
            $file
        );

        self::assertDirectoryExists(
            $cacheDirectory
        );
    }

    public function testClearIgnoresSymlinks(): void
    {
        if (!function_exists('symlink')) {
            self::markTestSkipped(
                'Symbolic links are not available.'
            );
        }

        $targetDirectory = $this->fixtureDirectory(
            'symlink'
        );

        $target = $targetDirectory
            . DIRECTORY_SEPARATOR
            . 'real.php';

        self::assertNotFalse(
            file_put_contents(
                $target,
                'cache'
            )
        );

        $link = $targetDirectory
            . DIRECTORY_SEPARATOR
            . 'link.php';

        if (!@symlink($target, $link)) {
            self::markTestSkipped(
                'Symbolic link creation is not authorized.'
            );
        }

        self::assertTrue(
            is_link(
                $link
            )
        );

        self::assertFileExists(
            $target
        );

        $cleaner = $this->cacheCleaner(
            'php'
        );

        self::assertSame(
            1,
            $cleaner->clear(
                $targetDirectory
            )
        );

        self::assertFileDoesNotExist(
            $target
        );

        self::assertTrue(
            is_link(
                $link
            ),
            'CacheCleaner must not delete the symbolic link.'
        );
    }

    public function testDeleteCanBeCalledTwice(): void
    {
        $file = $this->createFile(
            'cache.php'
        );

        $cleaner = $this->cacheCleaner(
            'php'
        );

        self::assertTrue(
            $cleaner->delete(
                $file
            )
        );

        self::assertTrue(
            $cleaner->delete(
                $file
            )
        );
    }

    private function fixtureDirectory(
        string $name
    ): string {
        $directory = $this->directory
            . DIRECTORY_SEPARATOR
            . $name;

        self::assertTrue(
            mkdir(
                $directory,
                0o777,
                true
            )
        );

        return $directory;
    }
}
