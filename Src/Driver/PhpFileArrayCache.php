<?php
declare(strict_types=1);

namespace NCache\Driver;

use NCache\Core\Files\PutData;
use NCache\Core\Files\ReadFile;
use NCache\Enum\CType;
use NCache\Driver\CacheDriver;

final class PhpFileArrayCache extends CacheDriver{
  
    public function __construct(
        string $file
    ){
       $files = str_contains($file,".php") ? 
       $file : "{$file}.php";
        parent::__construct($files);
    }

    protected function format():string{
        return var_export(
            $this->structure->toArray(),
            true
        );
    }

    protected function metaData():string{
        return "<?php ".PHP_EOL.
         "return ".$this->format().";";
    }

    public function save():bool{
        return (new PutData(
             $this->file,
            $this->metaData(),
            $this->tmp()
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