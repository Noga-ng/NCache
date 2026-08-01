<?php
declare(strict_types=1);

namespace NCache\Config;

final class CacheConfig{
    private static ?self $instance = null;
    private string $basePath = '';
    public function __construct(
        private readonly string $baseDir
    )
    {   
        $this->basePath = rtrim($this->baseDir,"/\\");
    }

    public static function config(string $baseDir = ''):CacheConfig{
        return self::$instance ??= new CacheConfig($baseDir);
    }

    public function getBasePath():string{
        return $this->basePath;
    }

    public function inspect(bool $autoDelete = false):static
    {
        return $this;
    }

    public static function resetInstance():void{
        self::$instance = null;
    }

}