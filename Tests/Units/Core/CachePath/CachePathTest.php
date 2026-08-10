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
use NCache\Tests\TestsUnit\TestsUnit;

final class CachePathTest extends TestsUnit
{
    protected function setUp(): void
    {
        $this->directory('ncache-tests-');
        parent::setUp();
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->directory);
        parent::tearDown();
    }

    public function testDirectoryDoesNotExistInitially(): void
    {
        $dir = "{$this->directory}/notExist";
        $cachePath = new CachePath($dir);

        self::assertFalse($cachePath->exists());
    }

    public function testValueReturnsTheConfiguredPath(): void
    {
        $cachePath = new CachePath($this->directory);

        self::assertSame(
            $this->directory,
            $cachePath->value()
        );
    }

    public function testItCanBeConvertedToString(): void
    {
        $cachePath = new CachePath($this->directory);

        self::assertSame(
            $this->directory,
            (string) $cachePath
        );
    }

    public function testGetPathCreatesTheDirectory(): void
    {
        $cachePath = new CachePath($this->directory);

        $result = $cachePath->getPath();

        self::assertSame($this->directory, $result);
        self::assertDirectoryExists($this->directory);
        self::assertTrue($cachePath->exists());
    }

    public function testDirReturnsAPathWithSubdirectory(): void
    {
        $cachePath = new CachePath($this->directory);

        $subPath = $cachePath->dir('users');

        self::assertSame(
            $this->directory
            . DIRECTORY_SEPARATOR
            . 'users',
            $subPath->value()
        );
    }

    public function testDirDoesNotModifyTheOriginalInstance(): void
    {
        $cachePath = new CachePath($this->directory);

        $subPath = $cachePath->dir('users');
        $newSubPath = $cachePath->dir("client");

        self::assertNotSame($cachePath->value(), $subPath->value());
        self::assertSame($this->directory, $cachePath->getBasePath());
        self::assertNotSame($newSubPath->value(), $subPath->value());
    }

    public function testEmptyDirKeepsTheSamePath(): void
    {
        $cachePath = new CachePath($this->directory);

        $result = $cachePath->dir('');

        self::assertSame(
            $this->directory,
            $result->value()
        );
    }

    public function testDirRemovesExternalSlashes(): void
    {
        $cachePath = new CachePath($this->directory);

        $result = $cachePath->dir('/users');

        self::assertSame(
            $this->directory
            . DIRECTORY_SEPARATOR
            . 'users',
            $result->value()
        );
    }

}
