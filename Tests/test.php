<?php

use NCache\Core\Files\CacheDirectory;
use NCache\Enum\CType;
use NCache\NCache;

require __DIR__.'/../vendor/autoload.php';

NCache::config(__DIR__.'/../ncache.config.json')->use('admin');

// $n = new CacheDirectory([__DIR__]);
// $count = 0;
// foreach($n->iterate() as $file){
//         if($file instanceof SplFileInfo){
//                 if($file->isFile()){
//                         print $file->getPathname()."\n";
//                         $count++;
//                 }

//         }
// }

// print $count;

$s = NCache::clear(CType::ARRAY_PHP, 'default');


print $s;
