<?php

declare(strict_types=1);

use NCache\Config\Connection\MCached;
use NCache\Core\Clock\Duration;
use NCache\Enum\CType;
use NCache\NCache;

require __DIR__ . '/../vendor/autoload.php';

NCache::config(__DIR__ . "/../cache");

$data = ["data" => ["all" => 1200,"with key"]];
