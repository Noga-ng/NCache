<?php

use NCache\Config\CacheConfig;
use NCache\Config\Ttl\Duration;
use NCache\Core\Files\CacheCleaner;
use NCache\Core\Files\CacheDirectory;
use NCache\Enum\CType;
use NCache\NCache;

require __DIR__."/../vendor/autoload.php";

NCache::config(__DIR__."/../cache");

$data = ["noga","germainio","ultra max filex"];

$append = ["theme"=>"black forever"];

$n = NCache::key("noga",CType::JSON)->dir("json");

$n->set($data);

$n->append($append);

$n->signature($data+$append)
    ->ttl(Duration::month(2));

$r = $n->show();

$s = $n->put();

print_r([$r,$s]);
