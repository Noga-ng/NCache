<?php

declare(strict_types=1);

use NCache\Enum\CType;
use NCache\NCache;

require __DIR__.'/../vendor/autoload.php';

$file = __DIR__.'/../ncache.config.json';

NCache::config($file)->use('admin');

$n = NCache::key('noga', CType::JSON);


$n->set(fn()=>"ng");

$n->put();

$s = $n->get();

print_r($s);
