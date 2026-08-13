<?php

declare(strict_types=1);

use NCache\NCache;

require __DIR__ . '/../vendor/autoload.php';

$file = __DIR__ . "/../ncache.config.json";

NCache::config($file)
        ->use("admin");

$n = Ncache::key("noga");

$n->set(
    [
        "name" => "noga",
        "role" => "admin",
        "title" => "creator of NCache",
        "local" => "Madagascar,Toamasina",
    ]
);

$n->put();

$res = $n->get();

var_dump($res);
