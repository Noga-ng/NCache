<?php
declare(strict_types=1);

namespace NCache\Core;

use NCache\Config\CacheConfig;
use NCache\Exceptions\FailedCreationDirException;

final class GetPath{
    private string $realDir = "";
    private string $basePath = '';
    public function __construct(
        private readonly string $dir,
        private int $permission = 0777
    ){
        $this->basePath = CacheConfig::config()->getBasePath();
        $this->realDir = $this->basePath.DIRECTORY_SEPARATOR.trim($this->dir,'/');
    }

    public function getPath():string{
        if(
        !is_dir($this->realDir) && 
        !mkdir($this->realDir,$this->permission,true)
        ){
           throw new FailedCreationDirException("failed to create cache folder on {$this->realDir}");
        }   

        return $this->realDir;
    }

}