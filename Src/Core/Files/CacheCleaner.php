<?php
declare(strict_types=1);

namespace NCache\Core\Files;

use NCache\Exceptions\InvalidCacheArgumentException;

final class CacheCleaner
{
   
    /**
     * @param string[] $extensionAllowed
     */
    public function __construct(
        private readonly array $extensionAllowed
        ){}

    /**
     * @return string[]
     */
    public function extensionAllowed():array{
        return $this->extensionAllowed;
    }


    public function delete(string $filename): bool
    {
        if (!is_file($filename)) {
            return true;
        }

        return unlink($filename);
    }

    public function clear(array $dir):int{
        $count = 0;
        $f = new CacheDirectory($dir);
        foreach($f->iterate() as $file){
            if($file->isFile()){
                \unlink($file->getRealPath());
            }
        }
        
        return $count;
    }
  
}