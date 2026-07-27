<?php
declare(strict_types=1);

namespace NCache\Driver;

use NCache\Core\CacheItem\CacheItem;
use NCache\Core\Files\CacheCleaner;
use NCache\Core\Files\WriteFile;
use NCache\Core\Files\ReadFile;
use NCache\Driver\CacheDriver;
use NCache\Enum\CType;

final class JsonCache extends CacheDriver{

    public function __construct(CacheItem $item){
        parent::__construct($item);
        }

    protected function format(): string{
        return json_encode(
            $this->item->toArray(),
            JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR
        );
    }

    public function metaData(): array{
        $data = $this->get();
        unset($data['data']);
       return $data;
    }

    private function buildFile():string{
        $file = $this->item->file();
        return str_ends_with($file,".json") ?
         $file : 
         "{$file}.json";
    }

    public function save(): bool{
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
            CType::JSON
        ))->get();
    }

    public function exists(): bool{
        return is_file($this->buildFile());
    }

    public function getFile(): string{
        return $this->buildFile();
    }

    public function delete(): bool{
        return CacheCleaner::delete($this->buildFile());
    }

    public function clear(): int{
        return CacheCleaner::clear($this->buildFile());
    }

}