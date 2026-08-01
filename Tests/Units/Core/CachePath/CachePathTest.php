<?php

/**
 * objectif de test
 * restitution du chemin ;
 *conversion en chaîne ;
 *détection d’existence ;
 *création automatique du dossier ;
 *ajout d’un sous-dossier ;
 *immutabilité de dir() ;
 *nettoyage des slashs externes.
 */
declare(strict_types=1);

namespace NCache\Tests\Units\Core\CachePath;

use NCache\Core\CachePath;
use PHPUnit\Framework\TestCase;

final class CachePathTest extends TestCase
{
    private string $basePath;

    protected function setUp(): void
    {
        $this->basePath = sys_get_temp_dir()
            . DIRECTORY_SEPARATOR
            . 'ncache-tests-'
            . uniqid('', true);
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->basePath);
    }

    public function testValueReturnsTheConfiguredPath(): void
    {
        $cachePath = new CachePath($this->basePath);

        self::assertSame(
            $this->basePath,
            $cachePath->value()
        );
    }

    public function testItCanBeConvertedToString(): void
    {
        $cachePath = new CachePath($this->basePath);

        self::assertSame(
            $this->basePath,
            (string) $cachePath
        );
    }

    public function testDirectoryDoesNotExistInitially(): void
    {
        $cachePath = new CachePath($this->basePath);

        self::assertFalse($cachePath->exists());
    }

    public function testGetPathCreatesTheDirectory(): void
    {
        $cachePath = new CachePath($this->basePath);

        $result = $cachePath->getPath();

        self::assertSame($this->basePath, $result);
        self::assertDirectoryExists($this->basePath);
        self::assertTrue($cachePath->exists());
    }

    public function testDirReturnsAPathWithSubdirectory(): void
    {
        $cachePath = new CachePath($this->basePath);

        $subPath = $cachePath->dir('users');

        self::assertSame(
            $this->basePath
            . DIRECTORY_SEPARATOR
            . 'users',
            $subPath->value()
        );
    }

    public function testDirDoesNotModifyTheOriginalInstance(): void
    {
        $cachePath = new CachePath($this->basePath);

        $subPath = $cachePath->dir('users');
        $newSubPath = $cachePath->dir("client");

        self::assertNotSame($cachePath->value(), $subPath->value());
        self::assertSame($this->basePath, $cachePath->geBasePath());
        self::assertNotSame($newSubPath->value(),$subPath->value());
    }

    public function testEmptyDirKeepsTheSamePath(): void
    {
        $cachePath = new CachePath($this->basePath);

        $result = $cachePath->dir('');

        self::assertSame(
            $this->basePath,
            $result->value()
        );
    }

    public function testDirRemovesExternalSlashes(): void
    {
        $cachePath = new CachePath($this->basePath);

        $result = $cachePath->dir('/users');

        self::assertSame(
            $this->basePath
            . DIRECTORY_SEPARATOR
            . 'users',
            $result->value()
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