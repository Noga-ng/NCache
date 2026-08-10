<?php
require __DIR__.'/../vendor/autoload.php';
use NCache\Config\Connection\RedisConn;

$r = new RedisConn();

$c = $r->connect();

$s = $r->isConnected();

print_r($s);