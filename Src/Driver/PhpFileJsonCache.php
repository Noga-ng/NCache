<?php
declare(strict_types=1);

namespace NCache\Driver;

use NCache\Core\Files\WriteFile;
use NCache\Core\Files\ReadFile;
use NCache\Driver\CacheDriver;
use NCache\Enum\CType;

final class PhpFileJsonCache extends CacheDriver{

    public function __construct(
        string $file
        ){
        $files = str_contains($file,".json") ?
        $file : "{$file}.json";

        parent::__construct($files);
    }

    protected function format(): array{
        return $this->structure->toArray();
    }

    protected function metaData(): string{
        return json_encode(
           $this->format(),
           JSON_PRETTY_PRINT|JSON_THROW_ON_ERROR
        );
    }

    public function save(): bool{
        return (new WriteFile(
            $this->file,
            $this->metaData(),
            $this->tmp()
        ))->save();
    }

    /**
     * @return mixed
     */
    public function get(): mixed{
        return (new ReadFile(
            $this->file,
            CType::JSON
        ))->get();
    }

    public function exists(): bool{
        return is_file($this->file);
    }

}