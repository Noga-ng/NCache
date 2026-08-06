<?php

// écriture atomique par fichier temporaire ;
// remplacement du fichier cible avec rename() ;
// contenu texte, UTF-8, binaire et volumineux ;
// écrasement d’un cache existant ;
// nettoyage des fichiers .tmp ;
// refus d’écrire dans un dossier inexistant ;
// absence de création automatique du dossier.

declare(strict_types=1);

namespace NCache\Tests\Units\Core\Files;

use NCache\Core\Files\WriteFile;
use NCache\Exceptions\FailedWriteCacheException;
use NCache\Tests\TestsUnit\TestsUnit;

final class WriteFileTest extends TestsUnit
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->directory('ncache-write-file-tests-');
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->directory);
        parent::tearDown();
    }

    public function testSaveCreatesTheTargetFile(): void
    {
        $file = $this->directory
            . DIRECTORY_SEPARATOR
            . 'cache.json';

        $writer = new WriteFile(
            $file,
            '{"name":"Noga"}'
        );

        $result = $writer->save();

        self::assertTrue($result);
        self::assertFileExists($file);
    }

    public function testSaveWritesTheExpectedContent(): void
    {
        $file = $this->directory
            . DIRECTORY_SEPARATOR
            . 'cache.txt';

        $data = 'NCache content';

        (new WriteFile($file, $data))->save();

        self::assertSame(
            $data,
            file_get_contents($file)
        );
    }

    public function testSaveCanWriteEmptyContent(): void
    {
        $file = $this->directory
            . DIRECTORY_SEPARATOR
            . 'empty.txt';

        $result = (new WriteFile($file, ''))->save();

        self::assertTrue($result);
        self::assertFileExists($file);
        self::assertSame('', file_get_contents($file));
        self::assertSame(0, filesize($file));
    }

    public function testSaveCanWriteLargeContent(): void
    {
        $file = $this->directory
            . DIRECTORY_SEPARATOR
            . 'large.txt';

        $data = str_repeat('NCache-', 20_000);

        (new WriteFile($file, $data))->save();

        self::assertSame(
            strlen($data),
            filesize($file)
        );

        self::assertSame(
            $data,
            file_get_contents($file)
        );
    }

    public function testSaveReplacesAnExistingFile(): void
    {
        $file = $this->directory
            . DIRECTORY_SEPARATOR
            . 'cache.txt';

        file_put_contents($file, 'old value');

        (new WriteFile($file, 'new value'))->save();

        self::assertSame(
            'new value',
            file_get_contents($file)
        );
    }

    public function testSaveDoesNotLeaveTemporaryFiles(): void
    {
        $file = $this->directory
            . DIRECTORY_SEPARATOR
            . 'cache.txt';

        (new WriteFile($file, 'content'))->save();

        $temporaryFiles = glob(
            $this->directory
            . DIRECTORY_SEPARATOR
            . '*.tmp'
        );

        self::assertSame([], $temporaryFiles);
    }

    public function testOriginalFileRemainsUntouchedUntilReplacement(): void
    {
        $file = $this->directory
            . DIRECTORY_SEPARATOR
            . 'cache.txt';

        file_put_contents($file, 'old content');

        $writer = new WriteFile(
            $file,
            'new content'
        );

        self::assertSame(
            'old content',
            file_get_contents($file)
        );

        $writer->save();

        self::assertSame(
            'new content',
            file_get_contents($file)
        );
    }

    public function testSaveSupportsBinaryData(): void
    {
        $file = $this->directory
            . DIRECTORY_SEPARATOR
            . 'binary.cache';

        $data = "\x00\x01\x02\xFF\x10";

        (new WriteFile($file, $data))->save();

        self::assertSame(
            $data,
            file_get_contents($file)
        );
    }

    public function testSaveSupportsUtf8Content(): void
    {
        $file = $this->directory
            . DIRECTORY_SEPARATOR
            . 'utf8.txt';

        $data = 'Données de cache malagasy — Toamasina';

        (new WriteFile($file, $data))->save();

        self::assertSame(
            $data,
            file_get_contents($file)
        );
    }

    public function testSaveThrowsExceptionWhenDirectoryDoesNotExist(): void
    {
        $file = $this->directory
            . DIRECTORY_SEPARATOR
            . 'missing'
            . DIRECTORY_SEPARATOR
            . 'cache.txt';

        $this->expectException(
            FailedWriteCacheException::class
        );

        $this->expectExceptionMessage(
            "does not exist"
        );

        (new WriteFile($file, 'content'))->save();
    }

    public function testSaveDoesNotCreateMissingDirectoryAutomatically(): void
    {
        $missingDirectory = $this->directory
            . DIRECTORY_SEPARATOR
            . 'missing';

        $file = $missingDirectory
            . DIRECTORY_SEPARATOR
            . 'cache.txt';

        try {
            (new WriteFile($file, 'content'))->save();

            self::fail(
                'FailedWriteCacheException was not thrown.'
            );
        } catch (FailedWriteCacheException) {
            self::assertDirectoryDoesNotExist(
                $missingDirectory
            );

            self::assertFileDoesNotExist($file);
        }
    }

    public function testFailedWriteDoesNotLeaveTemporaryFiles(): void
    {
        $missingDirectory = $this->directory
            . DIRECTORY_SEPARATOR
            . 'missing';

        $file = $missingDirectory
            . DIRECTORY_SEPARATOR
            . 'cache.txt';

        try {
            (new WriteFile($file, 'content'))->save();

            self::fail(
                'FailedWriteCacheException was not thrown.'
            );
        } catch (FailedWriteCacheException) {
            $temporaryFiles = glob(
                $this->directory
                . DIRECTORY_SEPARATOR
                . '*.tmp'
            );

            self::assertSame([], $temporaryFiles);
        }
    }

    public function testMultipleSuccessiveWritesReplaceTheContent(): void
    {
        $file = $this->directory
            . DIRECTORY_SEPARATOR
            . 'cache.txt';

        (new WriteFile($file, 'version 1'))->save();
        (new WriteFile($file, 'version 2'))->save();
        (new WriteFile($file, 'version 3'))->save();

        self::assertSame(
            'version 3',
            file_get_contents($file)
        );
    }

    public function testWrittenFileIsReadable(): void
    {
        $file = $this->directory
            . DIRECTORY_SEPARATOR
            . 'cache.txt';

        (new WriteFile($file, 'content'))->save();

        self::assertIsReadable($file);
    }

}