<?php

use NCache\Config\Ttl\Duration;
use NCache\Enum\CType;
use NCache\NCache;

require __DIR__."/../vendor/autoload.php";

Ncache::config(__DIR__."/../cache")->inspect();

$data = [
"Pays"=>"madagascar",
"Lang"=>"Malagasy",
"City"=>"Toamasina"
];

$append = ["Postal"=>501];
$append2 = [12=>125.2];

$n = NCache::key("info",CType::JSON)->dir("Json");

$n->ttl(Duration::make(12,24,30));

$n->signature($data);

$n->set($data);

$n->append($append);

$n->store();

$s = $n->get();

print_r($s);