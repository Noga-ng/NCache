<?php
declare(strict_types=1);

namespace NCache\Driver;

use NCache\Core\CacheItem\CacheItem;
use NCache\Core\Files\WriteFile;
use NCache\Core\Files\ReadFile;
use NCache\Enum\CType;
use NCache\Driver\CacheDriver;

final class SerializeCache extends CacheDriver{
  
    public function __construct(CacheItem $item){
        parent::__construct($item);
    }

    public function metaData(): array{
        $data = $this->get();
        return array_diff(array_keys($data),["data"]);
    }

    protected function format():string{
        return serialize($this->item->toArray());
    }

    private function buildFile():string{
        return $this->item->file().".nc";
    }

    public function save():bool{
        return (new WriteFile(
             $this->buildFile(),
            $this->format()
        ))->save();
    }

    /**
     * @return mixed
     */
    public function get(): mixed{
        return (new ReadFile(
            $this->buildFile(),
            CType::SERIALIZE
            ))->get();
    }

    public function getFile(): string{
        return $this->buildFile();
    }

    public function exists(): bool{
        return is_file($this->buildFile());
    }

    public function delete(): bool{
        return true;
    }

    public function clear():int{
        return 0;
    }
    
}