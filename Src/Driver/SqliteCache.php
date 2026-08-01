<?php 
declare(strict_types=1);

/**
 * INSERT INTO caches (...)
*VALUES (...)
 *ON CONFLICT(keys) DO UPDATE SET
  *  signature = excluded.signature,
   * ttl = excluded.ttl,
    *expiresAt = excluded.expiresAt,
    *data = excluded.data
 */
namespace NCache\Driver;

use NCache\Config\Connection\SQLitePdo;
use NCache\Core\CacheItem\CacheItem;
use NCache\Driver\CacheDriver;
use NCache\Exceptions\FailedWriteCacheException;

final class SqliteCache extends CacheDriver{

    private SQLitePdo $conn;

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
            keys TEXT UNIQUE NOT NULL,
            signature TEXT,
            ttl INTEGER NULL,
            expiresAt INTEGER NULL,
            data TEXT NULL
            )"  
        );

       $this->conn->execute(
            'INSERT INTO caches
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

        $lastId = $this->conn->lastId();

        if(!$lastId){
            throw new FailedWriteCacheException("failed to write on insert this cache");
        }

       return true;

    }

    /**
     * @return array<array-key,mixed>
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
     * @return array<array-key,mixed>
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
        $this->conn->execute(
            "DELETE FROM caches WHERE keys = :keys",
            [":keys"=>$this->item->hashedKey()]
        );

        return true;
    }

    public function clear():int{
        /**
         * @var array<int>
         */
        $count = $this->conn->get(
            "SELECT count(id) AS total FROM caches",
            fetchMode:\PDO::FETCH_COLUMN
        );

        $this->conn->execute("DELETE FROM caches");

        return $count['total'];
    }

}