<?php

declare(strict_types=1);
require __DIR__.'/../vendor/autoload.php';

use NCache\NCache;

$file = __DIR__.'/../ncache.config.json';

NCache::config($file)
        ->use('admin');

$n = NCache::key('noga');

$n->set(['noga','germainio']);

$n->put();

$s = $n->get();

print_r([
    $s,
]);
