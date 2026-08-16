<?php

declare(strict_types=1);

use NCache\NCache;

require __DIR__.'/../vendor/autoload.php';

$file = __DIR__.'/../ncache.config.json';

NCache::config($file)->use('admin');

$n = NCache::key('noga');


$n->set('my data');
$n->tags();
$n->put();

$s = $n->getTags();

print_r($s);
