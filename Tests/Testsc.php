<?php

use NCache\Core\CacheItem\CacheItem;
use NCache\Core\CachePath;
use NCache\Core\Clock\Duration;
use NCache\Core\Hash;
use NCache\Enum\CType;
use NCache\NCache;

require __DIR__."/../vendor/autoload.php";

$c = NCache::config(__DIR__."/../Cache");

$h = new Hash(null);

print_r($h->get());