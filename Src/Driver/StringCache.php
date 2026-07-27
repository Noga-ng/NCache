<?php
declare(strict_types=1);

namespace NCache\Driver;

use NCache\Core\CacheItem\CacheItem;
use NCache\Core\Files\ReadFile;
use NCache\Core\Files\WriteFile;
use NCache\Exceptions\InvalidCacheArgumentException;

final class StringCache extends CacheDriver{

    public function __construct(CacheItem $item){
        parent::__construct($item);
    }

    
    public function exists(): bool{
        return is_file($this->buildFile());
    }

    public function metaData(): array{
        return [];
    }

    protected function format(): string
    {
        if (!\is_string($this->item->getData())) {
            throw new InvalidCacheArgumentException(
                'StringFileCache accepts only string data.'
            );
        }

        return (string)$this->item->getData();
    }

    public function save(): bool
    {
        return (new WriteFile(
            $this->buildFile(),
            $this->format()
        ))->save();
    }

    public function get(): string{
       return (new ReadFile(
        $this->buildFile(),
        $this->item->type()
       ))->get();
    }

    public function buildFile():string{
        return $this->item->file();
    }

    public function getFile(): string{
        return $this->buildFile();
    }


    public function delete(): bool{
        return true;
    }


    public function clear(): int{
        return 0;
    }
}