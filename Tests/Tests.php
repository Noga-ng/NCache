<?php
require __DIR__.'/../vendor/autoload.php';
use NCache\Config\CacheConfig;
use NCache\Config\Connection\SqlitePdo;
use NCache\Config\Ttl\Duration;
use NCache\Core\CachePath;
use NCache\Core\Files\ReadFile;
use NCache\Core\Files\WriteFile;
use NCache\Enum\CType;
use NCache\NCache;
use Pdo\Pgsql;

CacheConfig::config(__DIR__."/../cache");

// 1 minute = 60 secondes
// 1 heure  = 3600 secondes
// 1 jour   = 86400 secondes

// 1 minute   = 60
// 5 minutes  = 300
// 1 heure    = 3600
// 12 heures  = 43200
// 1 jour     = 86400
// 7 jours    = 604800
// 30 jours   = 2592000

// echo Duration::make(30,10,50);

// echo round(2602200 / 86400) .PHP_EOL;
// echo 30 * 86400 .PHP_EOL;
// echo 2602200 - 2592000 .PHP_EOL;
// echo 10200 / 3600 .PHP_EOL;
// echo 2 * 3600 .PHP_EOL;
// echo 10200 - 7200 .PHP_EOL;
// echo 3000 / 60 .PHP_EOL;


// return  [
// 'Cache_noga'=>[  //cache key
// 'ttl'=>3600,
// 'expiredAt'=>1784973600,
// 'file'=>__DIR__."cache/cache.php"
// ],
// ];

$p = NCache::key('noga',CType::SQLite);

$c = $p->signature("noga")
->ttl(Duration::minutes(1))
->set(["noga","germainio"]);

$g = $p->get();

var_dump($g);