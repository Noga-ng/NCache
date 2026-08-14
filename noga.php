<?php

use NCache\NCache;

require __DIR__.'/vendor/autoload.php';

$file = __DIR__.'/ncache.config.json';

NCache::config($file);

require __DIR__.'/Tests/Tests.php';
require __DIR__.'/Tests/test.php';
