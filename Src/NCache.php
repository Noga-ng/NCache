<?php
declare(strict_types=1);

namespace NCache;

use NCache\Config\CacheConfig;
use NCache\Config\Ttl\Expiration;
use NCache\Contract\CacheInterface;
use NCache\Core\CacheItem\CacheItem;
use NCache\Core\CachePath;
use NCache\Core\Files\CacheCleaner;
use NCache\Core\Hash;
use NCache\Driver\CacheDriver;
use NCache\Enum\CType;
use NCache\Registry\DriverRegistry;

final class NCache implements CacheInterface{

    private CacheItem $cacheItem;
    public function __construct(string $key,CType $type){

        $basePath = new CachePath(CacheConfig::config()->getBasePath());
        $this->cacheItem = new CacheItem($key,$type,$basePath);
    }

    public static function key(string $key,CType $type):static{
        $instance = new NCache($key,$type);
        $instance->cacheItem->setName($key);
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
     * @return NCache
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
     * @param array<mixed>|bool|int|string $data
     * @return bool
     */
    public function set(mixed $data):bool{
        $this->cacheItem->setData($data);
        return $this->driver()->save();
    }

    public function delete(): bool{
        return (new CacheCleaner())
        ->delete(
            $this->driver()
                    ->getFile()
        );
    }

    /**
     * @param string|null $dir
     * @return int
     */
    public static function clear(?string $dir = null):int{
        return (new CacheCleaner())->clear($dir);
    }

    public function get(): mixed{
    $data = $this->driver()->get();

    if ($data === null) {
        return null;
    }

    if (
        isset($data['expiredAt']) &&
        (new Expiration(
            $data['ttl'],
            $data['expiredAt']))->expired()
    ) {
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

    public function has(): bool{
        return $this->driver()->exists();
    }

    /**
     * @param string|array<mixed>|int|null $data
     * @return bool
     */
    public function hasValidSignature(mixed $data): bool{
        $signature = (new Hash($data))->get();
        $cache = $this->driver()->metaData();

        return (\is_array($cache) && 
        isset($cache['signature']) && 
        $cache['signature'] === $signature
        );
    }

    /**
     * @return CacheDriver
     */
    private function driver():CacheDriver{
        return DriverRegistry::make(
           $this->cacheItem
        );
    }
  
}