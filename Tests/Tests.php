<?php

declare(strict_types=1);

use NCache\Enum\CType;
use NCache\NCache;

require __DIR__.'/../vendor/autoload.php';

$file = __DIR__.'/../ncache.config.json';

NCache::config(($file))
->use('admin');

$n = NCache::key('germainio', CType::ARRAY_PHP);
$data = [
    'noga',
    'new vibe',
    'germainio' => 150000,
];

if (!$n->hasValidSignature($data)) {
    $n->ttl();
    $n->signature($data);
    $n->set($data);
    $n->put();
}

$s = $n->get();

print_r($s);
