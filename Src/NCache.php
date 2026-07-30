<?php
declare(strict_types=1);

namespace NCache;

use NCache\Config\CacheConfig;
use NCache\Config\Ttl\Expiration;
use NCache\Contract\CacheInterface;
use NCache\Core\CacheItem\CacheItem;
use NCache\Core\CachePath;
use NCache\Core\Hash;
use NCache\Driver\CacheDriver;
use NCache\Enum\CType;
use NCache\Registry\DriverRegistry;

final class NCache implements CacheInterface{

    private CacheItem $cacheItem;
    private function __construct(string $key,CType $type){

        $basePath = new CachePath(CacheConfig::config()->getBasePath());
        $this->cacheItem = new CacheItem($key,$type,$basePath);
    }

    public static function key(string $key,CType $type):static{
        $instance = new NCache($key,$type);
        return $instance;
    }

    public function dir(string $dir):static{
        $this->cacheItem->setDir($dir);
        return $this;
    }

    /**
     *
     * Définit la valeur représentant l'état de la ressource.
     * Cette valeur est transformée en une signature interne
     * afin de détecter les changements.
     * @param array<mixed>|float|int|string $signature
     * @return static
     */
    public function signature(mixed $signature):static{
       $this->cacheItem->setSignature($signature);
        return $this;
    }

    public function ttl(int $ttl): static{
       $this->cacheItem->setTtl($ttl);
        return $this;
    }
    
    /**
     * @param array<mixed>|string|int|bool $data
     * @return static
     */
    public function set(mixed $data):static{
        $this->cacheItem->setData($data);
        return $this;
    }

    public function put():bool{
        return $this->driver()->save();
    }

    public function get(): mixed{
    $data = $this->driver()->get();

    if ($data === null) {
        return null;
    }

    $ttl = $data['ttl'] ?? null;

    $expiredAt = $data['expiresAt'] ?? null;

    if ($ttl !== null && !\is_int($ttl)) {
    throw new \UnexpectedValueException(
        'The cache TTL must be an integer or null.'
    );
    }

    if ($expiredAt !== null && !\is_int($expiredAt)) {
        throw new \UnexpectedValueException(
            'The cache expiration timestamp must be an integer or null.'
        );
    }

    $expiration = new Expiration($ttl, $expiredAt);

    if (isset($expiredAt) && $expiration->expired()) {
        $this->delete();
        return null;
    }
        return $data;
    }

      /**
       * @return array<string,mixed>
       */
    public function show():array{
        return $this->cacheItem->toArray();
    }

     public function delete(): bool{
        return $this->driver()->delete();
    }

    /**
     * @param CType $type
     * @param string $dir
     * @return int
     */
    public static function clear(CType $type,string $dir = ""):int{
        $instance = new self("",$type);
        $instance->cacheItem->setDir($dir);
        return $instance->driver()->clear();
    }

    public function has(): bool{
        return $this->driver()->exists();
    }

    /**
     * @param string|int|array<mixed>|float $data
     * @return bool
     */
    public function hasValidSignature(mixed $data): bool{
        $signature = (new Hash($data))->get();
        $cache = $this->driver()->metaData();

        return (isset($cache['signature']) && 
        $cache['signature'] === $signature );
    }

    /**
     * @return CacheDriver
     */
    private function driver():CacheDriver{
        return DriverRegistry::make(
           $this->cacheItem
        );
    }

    /**
     * @param string $baseDir
     * @return CacheConfig
     */
    public static function config(string $baseDir):CacheConfig{
        return CacheConfig::config($baseDir);
    }
  
}