<?php declare(strict_types=1);

namespace NCache;

use NCache\Config\CacheConfig;
use NCache\Contract\CacheInterface;
use NCache\Contract\Clock;
use NCache\Core\CacheItem\CacheItem;
use NCache\Core\Clock\SystemClock;
use NCache\Core\TtlManager\TtlManager;
use NCache\Core\CachePath;
use NCache\Core\Hash;
use NCache\Driver\CacheDriver;
use NCache\Enum\CType;
use NCache\Exceptions\CacheHandleException;
use NCache\Exceptions\InvalidCacheArgumentException;
use NCache\Registry\CacheRegistry;
use NCache\Registry\DriverRegistry;
use Throwable;

/**
 * @phpstan-type ItemData array<array-key,mixed>|string|int|bool|float|null
 */
final class NCache implements CacheInterface
{
    private CacheItem $cacheItem;
    private Clock $clock;

    /**
     * @param string $key
     * @param CType $type
     */
    private function __construct(string $key, CType $type)
    {
        $basePath = new CachePath(CacheConfig::config()->getBasePath());
        $this->cacheItem = new CacheItem($key, $type, $basePath);
        $this->clock = new SystemClock();
    }

    /**
     * @param string $key
     * @param CType $type
     * @return static
     */
    public static function key(string $key, CType $type): static
    {
        self::obligatorKey($key);
        $instance = new NCache($key, $type);
        return $instance;
    }

    /**
     * @param non-empty-string $dir
     * @return static
     */
    public function dir(string $dir): static
    {
        $this->cacheItem->setDir($dir);
        return $this;
    }

    /**
     * Définit la valeur représentant l'état de la ressource.
     * Cette valeur est transformée en une signature interne
     * afin de détecter les changements.
     * @param ItemData $signature
     * @return static
     */
    public function signature(mixed $signature): static
    {
        $this->cacheItem->setSignature($signature);
        return $this;
    }

    /**
     * @param positive-int|null $ttl
     * @return static
     */
    public function ttl(?int $ttl): static
    {
        $this->cacheItem->setTtl($ttl, $this->clock);
        return $this;
    }

    /**
     * @param ItemData $data
     * @return static
     */
    public function set(mixed $data): static
    {
        $this->cacheItem->setData($data);
        return $this;
    }

    /**
     * @param ItemData $data
     * @return static
     */
    public function append(mixed $data): static
    {
        $this->cacheItem->appendData($data);
        return $this;
    }

    public function has(): bool
    {
        $registry = $this->registry();

        if (!$registry->has()) {
            return false;
        }

        if ($this->ttlManager($registry)->isExpired()) {
            $this->delete();

            return false;
        }

        return $this->driver()->exists();
    }

    public function put(): bool
    {
        try {
            $driver = $this->driver();
            $registry = $this->registry();

            $registry->setFile($driver->getFile());

            $this
                ->ttlManager($registry)
                ->preserveStoredExpiration();

            if (!$driver->save()) {
                return false;
            }

            if (!$registry->save()) {
                $driver->delete();

                return false;
            }

            return true;
        } catch (Throwable $e) {
            CacheHandleException::handle($e);

            return false;
        }
    }

    /**
     * @return array<mixed>|int|string|null
     */
    public function get(): mixed
    {
        $registry = $this->registry();

        if (!$registry->has()) {
            return null;
        }

        $driver = $this->driver();

        if (!$driver->exists()) {
            $registry->remove();

            return null;
        }

        if ($this->ttlManager($registry)->isExpired()) {
            $driver->delete();
            $registry->remove();

            return null;
        }

        return $driver->get();
    }


    /**
     * @return array<string, array{
     * type: string,     
     * name: string, 
     * key: string, 
     * file: string|null, 
     * signature: string|null, 
     * ttl: int|null, 
     * expiresAt: int|null
     * }>
     */
    public function getRegistry():array
    {
        return $this->registry()->getAll();
    }

    /**
     * @return array<string,mixed>
     */
    public function show(): array
    {
        return $this->cacheItem->toArray();
    }

    public function ttlRemaining():?int
    {
        return $this->ttlManager(
            $this->registry()
            )->remaining();
    }

    public function ttlState():?string{
        return $this->ttlManager($this->registry())->state();
    }

    public function delete(): bool
    {
        $driver = $this->driver();
        $registry = $this->registry();

        if (!$driver->delete()) {
            return false;
        }

        return $registry->remove();
    }

    /**
     * @param CType $type
     * @param string $dir
     * @return int
     */
    public static function clear(CType $type, string $dir = ''): int
    {
        $instance = new self('__internal__', $type);

        if ($dir !== '') {
            $instance->cacheItem->setDir($dir);
        }

        $deleted = $instance->driver()->clear();

        $instance->registry()->removeMissing();

        return $deleted;
    }

    /**
     * @param ItemData $data
     * @return bool
     */
    public function hasValidSignature(mixed $data): bool
    {
        $registry = $this->registry();
        $signature = (new Hash($data))->get();
        $cache = $registry->get();

        return (isset($cache['signature']) &&
            $cache['signature'] === $signature);
    }


    /**
     * @return CacheDriver
     */
    private function driver(): CacheDriver
    {
        return DriverRegistry::make(
            $this->cacheItem
        );
    }

    private function ttlManager(CacheRegistry $cacheRegistry): TtlManager
    {
        return new TtlManager(
            $this->cacheItem,
            $cacheRegistry,
            $this->clock
        );
    }

    private function registry(): CacheRegistry
    {
        return new CacheRegistry(
            $this->cacheItem
        );
    }

    /**
     * @param string $baseDir
     * @return CacheConfig
     */
    public static function config(string $baseDir): CacheConfig
    {
        return CacheConfig::config($baseDir);
    }

    /**
     * @param null|string $key
     * @throws InvalidCacheArgumentException
     * @return void
     */
    private static function obligatorKey(?string $key = null):void{
        if($key === null){
              throw new InvalidCacheArgumentException(
                "Key cannot be empty"
        );

        }
      
    }

    public function item():CacheItem{
        return $this->cacheItem;
    }
}
