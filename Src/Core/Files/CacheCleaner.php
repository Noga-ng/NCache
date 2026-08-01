<?php
declare(strict_types=1);

namespace NCache\Core\Files;

use NCache\Exceptions\InvalidCacheArgumentException;


final class CacheCleaner
{
    /**
     * @param list<string> $extensionAllowed
     */
    public function __construct(
        private readonly array $extensionAllowed
        ){}

    public function delete(string $filename): bool
    {
        if (!is_file($filename) || !$this->isExtensionAllowed(\pathinfo($filename)['extension'])) {
            return true;
        }
        
        return $this->isUnlink($filename);
    }

    public function clear(string $dir):int
    {
      $count = 0;
      $files = new CacheDirectory([$dir]);

      foreach($files->iterate() as $file){
        if($file->isFile()){
            if($this->isExtensionAllowed($file->getExtension())){
                $this->isUnlink($file->getRealPath());
                $count++;
            }
        }

      }

      return $count;
    }

    private function isUnlink(string $filename):bool
    {
        if(!\unlink($filename)){
            throw new InvalidCacheArgumentException(
                "cannot delete this file {$filename}"
            );
        }
        return true;
    }

    public function isExtensionAllowed(string $extension):bool
    {
        return \in_array($extension,$this->extensionAllowed,true);
    }

    public function getExtensionAllowed():array{
        return $this->extensionAllowed;
    }
  
}