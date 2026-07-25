<?php
require __DIR__.'/../vendor/autoload.php';
use NCache\Config\CacheConfig;
use NCache\Config\Ttl\Duration;
use NCache\Enum\CType;
use NCache\NCache;
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

$d = [
'Cache_noga'=>[  //cache key
'ttl'=>3600,
'expiredAt'=>1784973600,
'file'=>__DIR__."cache/cache.php"
]
];

$f = NCache::key("noga",CType::JSON)
->dir("cacheTtl")
->signature($d)
->ttl(Duration::hours(4))
->set($d);

// 1784999000 1784999021

var_dump($f);