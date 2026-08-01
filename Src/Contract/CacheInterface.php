<?php
declare(strict_types=1);

namespace NCache\Contract;

use NCache\Enum\CType;

/**
 * @phpstan-type ItemData array<array-key,mixed>|string|int|bool|float
 */
interface CacheInterface{

    /**
     * @param non-empty-string $key
     * @param CType $type
     * @return static
     */
    public static function key(string $key,CType $type):static;

    public function has():bool;

    /**
     * @param ItemData $data
     * @return bool
     */
    public function hasValidSignature(mixed $data):bool;

    /**
     * @param ItemData $signature
     * @return static
     */
    public function signature(mixed $signature):static;

    /**
     * @param non-negative-int $ttl
     * @return static
     */
    public function ttl(int $ttl):static;

    /**
     * @param ItemData $data
     * @return static
     */
    public function set(mixed $data):static;

    /**
     * @param ItemData $data
     * @return static
     */
    public function append(mixed $data):static;

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