<?php
declare(strict_types=1);

namespace NCache\Driver;

use NCache\Core\CacheItem\CacheItem;
use NCache\Core\Files\CacheCleaner;

abstract class CacheDriver{

    protected CacheCleaner $cacheCleaner;
      public function __construct(
        protected CacheItem $item
        ){}
    /**
     * @return array<string,mixed>
     */
    public function show():array{
        return $this->item->toArray();
    }

    /**
     * @return string|null
     */
    abstract public function getFile():?string;

    abstract public function exists():bool;
    
     /**
     * @return string|int|array<mixed>|null
     */
    abstract protected function format():mixed;

    abstract public function save():bool;

    /**
     * @return string|array<mixed>|int|null
     */
    abstract public function get():mixed;

    abstract public function delete():bool;
    abstract public function clear():int;
}