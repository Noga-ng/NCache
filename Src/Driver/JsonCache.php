<?php

declare(strict_types=1);

namespace NCache\Driver;

use NCache\Core\CacheItem\CacheItem;
use NCache\Core\Files\CacheCleaner;
use NCache\Core\Files\WriteFile;
use NCache\Core\Files\ReadFile;
use NCache\Driver\CacheDriver;
use NCache\Enum\CType;
use NCache\Exceptions\InvalidCacheArgumentException;

final class JsonCache extends CacheDriver
{
    public function __construct(CacheItem $item)
    {
        parent::__construct($item);
        $this->cacheCleaner = new CacheCleaner(["json"]);
    }

    protected function format(): string
    {
        return json_encode(
            $this->item->getData(),
            JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR
        );
    }


    private function buildFile(): string
    {
        $file = $this->item->file();
        if (!\is_string($file)) {
            throw new InvalidCacheArgumentException(
                "file cannot be null"
            );
        }

        return str_ends_with($file, ".json")
         ? $file
         : "{$file}.json";
    }

    public function save(): bool
    {
        return (new WriteFile(
            $this->buildFile(),
            $this->format()
        ))->save();
    }

    /**
     * @return array<array-key, mixed>|string
     */
    public function get(): mixed
    {
        return (new ReadFile(
            $this->buildFile(),
            CType::JSON
        ))->get();
    }

    public function exists(): bool
    {
        return is_file($this->buildFile());
    }

    public function getFile(): string
    {
        return $this->buildFile();
    }

    public function delete(): bool
    {
        return $this->cacheCleaner
                ->delete($this->buildFile());
    }

    public function clear(): int
    {
        return $this->cacheCleaner
                ->clear($this->item->path());
    }

}
