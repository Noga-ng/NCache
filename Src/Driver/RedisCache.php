<?php
declare(strict_types=1);

namespace NCache\Driver;

use NCache\Core\CacheItem\CacheItem;
use NCache\Driver\CacheDriver;

final class RedisCache extends CacheDriver{

    public function __construct(CacheItem $item) {
        parent::__construct($item);
    }

    public function get(): mixed{
        return 0;
    }

    public function format(): mixed{
        return $this->item->toArray();
    }

    public function exists(): bool{
        return true;
    }

    public function save(): bool{

        if ($this->item->ttlValue() !== null) {
             $this->redis->setex(
                $this->item->hashedKey(),
                $this->item->ttlValue(),
                $this->item->getData()
            );

            return true;
        }

        return $this->redis->set(
            $this->item->hashedKey(),
                $this->item->getData()
        );
    }

    public function metaData():array{
        return [];
    }

    public function getFile(): string{
        return "";
    }
    
    public function delete(): bool{
        return true;
    }

    public function clear(): int{
        return 0;
    }
}