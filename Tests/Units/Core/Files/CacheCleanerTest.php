<?php

declare(strict_types=1);

namespace NCache\Tests\Units\Core\Files;

use NCache\Core\Files\CacheCleaner;
use NCache\Core\Files\WriteFile;
use PHPUnit\Framework\TestCase;

final class CacheCleanerTest extends TestCase
{
    private string $directory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->directory = sys_get_temp_dir()
            . DIRECTORY_SEPARATOR
            . 'ncache-cleaner-'
            . bin2hex(random_bytes(8));
 
        mkdir($this->directory, 0777, true);
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->directory);
    }

    public function testExtensionAllowedReturnsConstructorValue(): void
    {
        $cleaner = new CacheCleaner(['php', 'json']);

        self::assertSame(
            ['php', 'json'],
            $cleaner->getExtensionAllowed()
        );
    }

    public function testDeleteExistingFile(): void
    {
        $file = $this->createFile('cache.php');

        $cleaner = new CacheCleaner(['php']);

        self::assertTrue($cleaner->delete($file));
        self::assertFileDoesNotExist($file);
    }

    public function testDeleteMissingFileReturnsTrue(): void
    {
        $cleaner = new CacheCleaner(['php']);

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

        $cleaner = new CacheCleaner(['php', 'json']);

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

        $cleaner = new CacheCleaner(['php']);

        self::assertSame(
            1,
            $cleaner->clear("{$this->directory}")
        );

        self::assertFileDoesNotExist($file);
    }

    public function testClearReturnsZeroWhenNothingMatches(): void
    {
        $this->createFile('cache.txt');

        $cleaner = new CacheCleaner(['php']);

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

        $cleaner = new CacheCleaner(['php']);

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

    $cleaner = new CacheCleaner(['php']);

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

        $cleaner = new CacheCleaner(['php']);

        self::assertTrue($cleaner->delete($file));
        self::assertTrue($cleaner->delete($file));
    }

    private function createFile(
        string $name,
        string $content = 'cache'
    ): string {
        $file = $this->directory
            . DIRECTORY_SEPARATOR
            . $name;

        $dir = dirname($file);

        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        @(new WriteFile($file, $content))->save();

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

            if (is_dir($path) && !is_link($path)) {
                $this->removeDirectory($path);
            } else {
                @unlink($path);
            }
        }

        @rmdir($directory);
    }
}