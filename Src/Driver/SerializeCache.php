<?php
declare(strict_types=1);

namespace NCache\Driver;

use NCache\Core\CacheItem\CacheItem;
use NCache\Core\Files\CacheCleaner;
use NCache\Core\Files\WriteFile;
use NCache\Core\Files\ReadFile;
use NCache\Enum\CType;
use NCache\Driver\CacheDriver;

final class SerializeCache extends CacheDriver{

    public function __construct(CacheItem $item){
        parent::__construct($item);
        $this->cacheCleaner = new CacheCleaner(['nc']);
    }


    protected function format():string{
        return serialize($this->item->getData());
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
     * @return array<array-key, mixed>|string
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
        return $this->cacheCleaner
                ->delete($this->buildFile());
    }

    public function clear():int{
      return $this->cacheCleaner
               ->clear($this->item->path());
    }
    
}