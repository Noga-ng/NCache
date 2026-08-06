<?php

declare(strict_types=1);

namespace NCache\Tests\Units\Core\Files;

use NCache\Tests\TestsUnit\TestsUnit;

final class CacheCleanerTest extends TestsUnit
{

    protected function setUp(): void
    {
        parent::setUp();
        $this->directory('ncache-cleaner-');
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->directory);
        parent::tearDown();
    }

    public function testExtensionAllowedReturnsConstructorValue(): void
    {
        $cleaner = $this->cacheCleaner(['php', 'json']);

        self::assertSame(
            ['php', 'json'],
            $cleaner->getExtensionAllowed()
        );
    }

    public function testDeleteExistingFile(): void
    {
        $file = $this->createFile('cache.php');

        $cleaner = $this->cacheCleaner('php');

        self::assertTrue($cleaner->delete($file));
        self::assertFileDoesNotExist($file);
    }

    public function testDeleteMissingFileReturnsTrue(): void
    {
        $cleaner = $this->cacheCleaner('php');

        self::assertTrue(
            $cleaner->delete(
                "{$this->directory}/missing.php"
            )
        );
    }

    public function testClearDeletesOnlyAllowedExtensions(): void
    {
        $php = $this->createFile('a.php');
        $json = $this->createFile('b.json');
        $txt = $this->createFile('c.txt');

        $cleaner = $this->cacheCleaner(['php', 'json']);

        self::assertSame(
            2,
            $cleaner->clear($this->directory)
        );

        self::assertFileDoesNotExist($php);
        self::assertFileDoesNotExist($json);
        self::assertFileExists($txt);
    }

    public function testClearDeletesFilesRecursively(): void
    {
        mkdir("{$this->directory}/sub");

        $file = "{$this->directory}/sub/cache.php";

        file_put_contents($file, 'cache');

        $cleaner = $this->cacheCleaner('php');

        self::assertSame(
            1,
            $cleaner->clear("{$this->directory}")
        );

        self::assertFileDoesNotExist($file);
    }

    public function testClearReturnsZeroWhenNothingMatches(): void
    {
        $this->createFile('cache.txt');

        $cleaner = $this->cacheCleaner('php');

        self::assertSame(
            0,
            $cleaner->clear($this->directory)
        );
    }

    public function testClearDoesNotDeleteDirectories(): void
    {
        mkdir("{$this->directory}/cache");

        $this->createFile(
            'cache/test.php',
            'cache'
        );

        $cleaner = $this->cacheCleaner('php');

        $cleaner->clear($this->directory);

        self::assertDirectoryExists(
            "{$this->directory}/cache"
        );
    }

   public function testClearIgnoresSymlinks(): void
{
    if (!function_exists('symlink')) {
        self::markTestSkipped(
            'The link symbolic is not available.'
        );
    }

    $target = $this->createFile('real.php');
    $link = $this->directory . DIRECTORY_SEPARATOR . 'link.php';

    if (!@symlink($target, $link)) {
        self::markTestSkipped(
            'creation link symbolic is not authorized.'
        );
    }

    self::assertTrue(is_link($link));
    self::assertFileExists($target);

    $cleaner = $this->cacheCleaner('php');

    self::assertSame(
        1,
        $cleaner->clear($this->directory)
    );

    self::assertFileDoesNotExist($target);

    self::assertTrue(
        is_link($link),
        'CacheCleaner ne doit pas supprimer le lien symbolique.'
    );

}

    public function testDeleteCanBeCalledTwice(): void
    {
        $file = $this->createFile('cache.php');

        $cleaner = $this->cacheCleaner('php');

        self::assertTrue($cleaner->delete($file));
        self::assertTrue($cleaner->delete($file));
    }
 
}