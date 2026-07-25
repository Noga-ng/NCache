<?php
declare(strict_types=1);

namespace NCache\Driver;

use NCache\Structure\Structure;

abstract class CacheDriver{
    protected Structure $structure;

      public function __construct(
        protected readonly string $file
    ){}

    public function structure(Structure $structure):static{
        $this->structure = $structure;
        return $this;
    }

    protected function tmp():string{
        $file = dirname($this->file);
        return $file.DIRECTORY_SEPARATOR.bin2hex(random_bytes(16)).'.tmp';
    }

    /**
     * @return array<string,mixed>
     */
    public function show():array{
        return $this->structure->toArray();
    }

    public function getFile():string{
        return $this->file;
    }

    abstract public function exists():bool;
    
     /**
     * @return string|int|array<mixed>|null
     */
    abstract protected function format():mixed;

    abstract public function save():bool;

    /**
     * @return string|int|array<mixed>|null
     */
    abstract protected function metaData():mixed;

    /**
     * @return string|array<mixed>|int|null
     */
    abstract public function get():mixed;
}