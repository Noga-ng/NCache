<?php

declare(strict_types=1);

use NCache\Psr\PsrCache\CacheItemPool;

require __DIR__.'/../vendor/autoload.php';


$file = __DIR__.'/../ncache.config.json';

$pool = new CacheItemPool(
    $file,
    'admin',
);

$users = ['name' => 'noga','tel' => '0340488021'];

$item = $pool->getItem('users');
if (!$item->isHit()) {
    $item->set($users);
    $item->expiresAfter(3600);
    $pool->save($item);
}

$s = $users = $item->get();

print_r($s);
