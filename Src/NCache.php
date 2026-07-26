<?php
declare(strict_types=1);

namespace NCache;

use NCache\Config\Ttl\Expiration;
use NCache\Contract\CacheInterface;
use NCache\Core\Files\CacheCleaner;
use NCache\Core\GetPath;
use NCache\Core\Hash;
use NCache\Driver\CacheDriver;
use NCache\Enum\CType;
use NCache\Registry\DriverRegistry;
use NCache\Structure\Structure;

final class NCache implements CacheInterface {

    private Structure $structure;
    public function __construct(string $key,CType $type){
        $this->structure = new Structure($key,$type);
    }

    public static function key(string $key,CType $type):static{
        $instance = new NCache((new Hash($key))->get(),$type);
        $instance->structure->name = $key;
        return $instance;
    }

    public function dir(string $dir):static{
        $this->structure->dir = (new GetPath($dir))->getPath();
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
        $this->structure->signature = (new Hash($signature))->get();
        return $this;
    }

    public function ttl(int $ttl): static{
       $this->structure->ttl($ttl);
        return $this;
    }
    
    /**
     * @param array<mixed>|bool|int|string $data
     * @return bool
     */
    public function set(mixed $data): bool{
        $this->structure->data = $data;
        return $this->driver()
        ->structure($this->structure)
        ->save();
    }

    public function delete(): bool{
        return (new CacheCleaner())->delete($this->driver()->getFile());
    }

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

        return $data['data'];
    }

      /**
       * @return array<string,mixed>
       */
      public function show():array{
        return $this->structure->toArray();
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
        $cache = $this->driver()->get();

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
           $this->structure->type,
            $this->structure->file()
        );
    }
  

}