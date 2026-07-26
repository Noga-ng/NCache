<?php
declare(strict_types=1);

namespace NCache\Driver;

use NCache\Core\Files\WriteFile;
use NCache\Core\Files\ReadFile;
use NCache\Enum\CType;
use NCache\Driver\CacheDriver;

final class PhpFileArrayCache extends CacheDriver{
  
    public function __construct(
        string $file
    ){
       $files = "{$file}.nc";
        parent::__construct($files);
    }

    protected function format():string{
        return serialize($this->structure->toArray());
    }

    protected function metaData():string{
        return $this->format();
    }

    public function save():bool{
        return (new WriteFile(
             $this->file,
            $this->metaData()
        ))
        ->save();
    }

    /**
     * @return mixed
     */
    public function get(): mixed{
        return (new ReadFile(
            $this->file,
            CType::ARRAY
            ))->get();
    }

    public function exists(): bool{
        return is_file($this->file);
    }
    
}