<?php

use NCache\Core\Clock\Duration;
use NCache\Core\Files\CacheDirectory;
use NCache\Core\Files\ReadFile;
use NCache\Enum\CType;
use NCache\NCache;
require __DIR__."/../vendor/autoload.php";

NCache::config(__DIR__."/../Cache");

    function normalizePath(string $path): string 
    {
        return str_replace(
            ['/', '\\'],
            DIRECTORY_SEPARATOR,
            $path
        );
    }
  function extractPaths(array $files): array
    {
        $paths = array_map(
            fn (SplFileInfo $file): string =>
                normalizePath(
                    $file->getPathname()
                ),
            $files
        );

        sort($paths);

        return $paths;
    }

    $filesPaths = [__DIR__."/Units/Driver"];

    $dir = (new CacheDirectory($filesPaths))->iterate();

    $paths = extractPaths($dir);

    print_r($paths);