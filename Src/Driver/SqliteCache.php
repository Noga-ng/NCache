<?php 
declare(strict_types=1);

namespace NCache\Driver;

use NCache\Config\Connection\SQLitePdo;
use NCache\Core\CacheItem\CacheItem;
use NCache\Driver\CacheDriver;
use NCache\Exceptions\InvalidCacheArgumentException;

final class SqliteCache extends CacheDriver{

    private ?SQLitePdo $conn = null;

    public function __construct(CacheItem $item){
        parent::__construct($item);
        $this->conn = (new SQLitePdo($this->buildFile()));
    }

    protected function format(): string{
        return serialize($this->item->getData());
    }

    public function exists(): bool{
        $key = $this->conn->get(
            "SELECT keys FROM caches WHERE keys = :keys",
            [":keys"=>$this->item->hashedKey()]
        );

        return !empty($key);
    }

    public function buildFile():string{
        return $this->item->path()."/CacheDb/nc.db";
    }

      public function save(): bool
    {
        $this->conn->create(
            "CREATE TABLE IF NOT EXISTS caches(
            id INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
            type TEXT NOT NULL,
            keys TEXT NOT NULL,
            signature TEXT,
            ttl INTEGER NULL,
            expiresAt INTEGER NULL,
            data TEXT NULL
            )"  
        );

        $stmt = $this->conn->execute(
            'INSERT OR REPLACE INTO caches
             (type,keys,signature,ttl, expiresAt,data)
             VALUES (:type,:keys, :signature,:ttl, :expiresAt,:data)',
             [
            ':type'=>$this->item->typeName(),
            ':keys' => $this->item->hashedKey(),
            ':signature'=>$this->item->getSignature(),
            ':ttl'=>$this->item->ttlValue(),
            ':expiresAt' => $this->item->expiredAt(),
            ':data'=>$this->format()
            ]
        );

       if(!$stmt){
        throw new InvalidCacheArgumentException(
            "execution error : ".implode(',',$stmt->errorInfo())
        );
       }

       return true;

    }

    /**
     * @return array<int|string,mixed>
     */
    public function get():array{
        return $this->conn
                ->getAll(
                    "SELECT * FROM caches 
                          WHERE keys=:keys",
                    [':keys'=>$this->item->hashedKey()]
                );
    }

    /**
     * @return array<int|string,mixed>
     */
    public function metaData(): array{
        return $this->conn
                ->getAll(
                    "SELECT keys,signature,ttl,expiresAt
                         FROM caches
                         WHERE keys = :keys",
                    [':keys'=>$this->item->hashedKey()]
                );
    }

    public function getFile(): string{
        return $this->buildFile();
    }

     public function delete(): bool{
        return true;
    }

    public function clear():int{
        return 0;
    }

}