<?php
require __DIR__."/../vendor/autoload.php";

use NCache\Config\Ttl\Duration;
use NCache\Enum\CType;
use NCache\NCache;

NCache::config(__DIR__ . '/../cache');

$item = NCache::key('users', CType::JSON)
    ->dir('json')
    ->set([
        'id' => 1,
        'name' => 'Noga',
    ])
    ->set([
        'id' => 2,
        'name' => 'Germainio',
    ])
    ->set([
        'id' => 3,
        'name' => 'John',
    ])
    ->ttl(Duration::days(1))
    ->signature('users');

$item->put();

echo json_encode($item->show(), JSON_PRETTY_PRINT);

// result 
// {
//     "type": "JSON",
//     "name": "users",
//     "key": "48860f98be550e0ac7167fdc4036a2ff",
//     "signature": "7dfb4cf67742cb0660305e56ef816c53fcec892cae7f6ee39b75f34e659d672c",
//     "ttl": 86400,
//     "expiresAt": 1785337430,
//     "data": [
//         {
//             "id": 1,
//             "name": "Noga"
//         },
//         {
//             "id": 2,
//             "name": "Germainio"
//         },
//         {
//             "id": 3,
//             "name": "John"
//         }
//     ]
// }