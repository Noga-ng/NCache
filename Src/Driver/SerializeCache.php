<?php
declare(strict_types=1);

namespace NCache\Driver;

use NCache\Core\CacheItem\CacheItem;
use NCache\Core\Files\CacheCleaner;
use NCache\Core\Files\WriteFile;
use NCache\Core\Files\ReadFile;
use NCache\Enum\CType;
use NCache\Driver\CacheDriver;
use NCache\Exceptions\InvalidCacheArgumentException;

final class SerializeCache extends CacheDriver{

    public function __construct(CacheItem $item){
        parent::__construct($item);
        $this->cacheCleaner = new CacheCleaner(['nc']);
    }

    public function metaData(): array{
        $data = $this->get();

        if(!\is_array($data)){
            throw new InvalidCacheArgumentException(
                self::class." most be return array but ".\get_debug_type($data)." given"
            );
        }

        unset($data['data']);

        return $data;
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
        return $this->cacheCleaner->delete($this->buildFile());
    }

    public function clear():int{
        return $this->cacheCleaner->clear($this->item->path());
    }
    
}