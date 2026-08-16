<?php

declare(strict_types=1);

use NCache\NCache;
use NCache\Psr\SimpleCache\SimpleCache;

require __DIR__.'/../vendor/autoload.php';

$file = __DIR__.'/../ncache.config.json';


$s = new SimpleCache(
    $file,
    "admin"
);

$s->set("noga",["noga","germainio"]);

$s = $s->get("noga",["n"]);

print_r($s);