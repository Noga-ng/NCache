<?php
require __DIR__."/../vendor/autoload.php";

use NCache\Enum\CType;
use NCache\NCache;

NCache::config(__DIR__ . '/../cache');

$clear = NCache::clear(CType::JSON);

var_dump($clear);