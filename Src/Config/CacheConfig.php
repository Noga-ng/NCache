<?php
declare(strict_types=1);

namespace NCache\Config;

final class CacheConfig{
    private static ?self $instance = null;
    private string $basePath = '';
    public function __construct(
        private readonly string $baseDir
    ){   
        $this->basePath = trim($this->baseDir,"/");
    }

    public static function config(string $baseDir = ''):CacheConfig{
        if(self::$instance === null){
            self::$instance = new CacheConfig($baseDir);
        }
        return self::$instance;
    }

    public function getBasePath():string{
        return $this->basePath;
    }

}