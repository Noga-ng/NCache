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
        return [0=>""];
    }

    /**
     * @return string
     */
  protected function format(): string
{
    $data = array_map(
        static function (mixed $value): string {
            if (\is_array($value) || \is_object($value)) {
                return json_encode(
                    $value,
                    JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE
                );
            }

            return match (true) {
                \is_string($value) => $value,
                \is_int($value),
                \is_float($value) => (string) $value,
                \is_bool($value) => $value ? 'true' : 'false',
                $value === null => 'null',

                default => throw new \InvalidArgumentException(
                    \sprintf(
                        'the type %s cannot be convert to string.',
                        get_debug_type($value)
                    )
                ),
            };
        },
        $this->item->getData()
    );
    $content = implode(',', $data);

    return "type : {$this->item->typeName()};".PHP_EOL.
            "name : {$this->item->key()};".PHP_EOL.
            "signature : {$this->item->getSignature()};".PHP_EOL.
            "ttl : {$this->item->ttlValue()};".PHP_EOL.
            "expiresAt : {$this->item->expiredAt()}; ".PHP_EOL.
            "content : ".PHP_EOL.
            "{$content};".PHP_EOL;
}
    

    public function save(): bool
    {
        return (new WriteFile(
            $this->buildFile(),
            $this->format()
        ))->save();
    }

    /**
     * @return string
     */
    public function get(): string{
        $content = (new ReadFile(
        $this->buildFile(),
        $this->item->type()
       ))->get();
            
       if(!\is_string($content)){
                throw new InvalidCacheArgumentException("cannot return array on this StringCache");
            }

       return $content;
    }

    /**
     * @return string
     */
    public function buildFile():string{
        return (string)$this->item->file();
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