<?php
declare(strict_types=1);

namespace NCache\Contract;

use NCache\Enum\CType;

interface CacheInterface{

    public static function key(string $key,CType $type):static;
    public function has():bool;

    /**
     * @param string|array<mixed>|int|null $data
     * @return bool
     */
    public function hasValidSignature(mixed $data):bool;
    /**
     * @param string|int|iterable<mixed> $signature
     * @return static
     */
    public function signature(mixed $signature):static;
    public function ttl(int $ttl):static;

     /**
     * @param mixed[] $data
     * @return static
     */
    public function set(mixed ...$data):static;

    public function put():bool;
      /**
     * @return string|int|array<mixed>|null
     */
    public function get():mixed;
    public function delete():bool;

    /**
     * @param CType $type
     * @param string $dir
     * @return int
     */
    public static function clear(CType $type,string $dir):int;

}