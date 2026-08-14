<?php

declare(strict_types=1);
require __DIR__.'/../vendor/autoload.php';

use NCache\NCache;

$file = __DIR__.'/../ncache.config.json';

NCache::config($file)
        ->use('admin');

$data = ['noga','germainio'];

NCache::key('foo')->set($data)->put();
NCache::key('foo')->get();
NCache::key('foo')->has();
NCache::key('foo')->delete();

NCache::key('foo')->ttl()->put();       // defaultTtl
NCache::key('foo')->ttl(60)->put();     // override
NCache::key('foo')->put();              // forever

NCache::config($file)->use('users');
$user = NCache::key('foo')->put();

NCache::config()->use('admin');
// $user doit toujours utiliser users
