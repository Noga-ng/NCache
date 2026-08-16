# NCache PSR-6 Adapter

NCache provides a PSR-6 compatible cache adapter built on top of the native NCache API.

The adapter implements the standard PSR-6 interfaces:

```php
Psr\Cache\CacheItemInterface
Psr\Cache\CacheItemPoolInterface
```

It allows frameworks and libraries expecting a PSR-6 cache pool to use NCache without modifying the native NCache API.

## Requirements

- PHP >= 8.1
- NCache
- `psr/cache ^3.0`

Install the PSR cache interfaces with Composer:

```bash
composer require psr/cache:^3.0
```

## Architecture

The PSR-6 integration is composed of two main classes:

```text
PsrCacheItem
    |
    | CacheItemInterface
    |
    v
CacheItemPool
    |
    | CacheItemPoolInterface
    |
    v
NCache
    |
    v
Cache Drivers
```

### PsrCacheItem

`PsrCacheItem` represents a PSR-6 cache item in memory.

It contains:

- the cache key
- the cached value
- the hit/miss state
- the expiration time

Changing a `PsrCacheItem` does not immediately write anything to the cache backend.

### CacheItemPool

`CacheItemPool` manages cache items and communicates with NCache.

It is responsible for:

- retrieving items
- storing items
- deleting items
- clearing cache
- handling expiration
- deferred saves
- committing deferred items

## Creating a Cache Pool

Create a cache pool using an NCache configuration file:

```php
<?php

use NCache\Psr\PsrCache\CacheItemPool;

$pool = new CacheItemPool(
    __DIR__ . '/ncache.config.json',
);
```

The `default` profile is used automatically.

A specific profile can also be selected:

```php
$pool = new CacheItemPool(
    __DIR__ . '/ncache.config.json',
    'users',
);
```

## Selecting a Driver

A driver can optionally be fixed for the entire pool:

```php
use NCache\Enum\CType;
use NCache\Psr\PsrCache\CacheItemPool;

$pool = new CacheItemPool(
    __DIR__ . '/ncache.config.json',
    'users',
    CType::REDIS,
);
```

Every operation performed through this pool will use Redis.

If no driver is explicitly provided, the profile's configured `defaultDriver` is used.

## Basic Usage

Retrieve an item:

```php
$item = $pool->getItem(
    'user.42',
);
```

Check whether the item exists:

```php
if ($item->isHit()) {
    $user = $item->get();
}
```

When the item does not exist:

```php
$item = $pool->getItem(
    'user.42',
);

if (!$item->isHit()) {
    // Cache miss.
}
```

## Storing Data

A PSR-6 item is modified in memory first:

```php
$item = $pool->getItem(
    'user.42',
);

$item->set([
    'id' => 42,
    'name' => 'Noga',
]);
```

At this point the value has not yet been persisted.

Save it using the pool:

```php
$pool->save(
    $item,
);
```

Complete example:

```php
$item = $pool->getItem(
    'user.42',
);

if (!$item->isHit()) {
    $item->set([
        'id' => 42,
        'name' => 'Noga',
    ]);

    $pool->save(
        $item,
    );
}

$user = $item->get();
```

## Expiration

PSR-6 provides two expiration methods.

### expiresAfter()

Set the TTL in seconds:

```php
$item
    ->set($data)
    ->expiresAfter(
        3600,
    );

$pool->save(
    $item,
);
```

This stores the item for approximately one hour.

A `DateInterval` can also be used:

```php
use DateInterval;

$item
    ->set($data)
    ->expiresAfter(
        new DateInterval('PT1H'),
    );

$pool->save(
    $item,
);
```

### expiresAt()

An absolute expiration date can be provided:

```php
use DateTimeImmutable;

$item
    ->set($data)
    ->expiresAt(
        new DateTimeImmutable(
            '+1 hour',
        ),
    );

$pool->save(
    $item,
);
```

Passing `null` removes the explicitly configured expiration:

```php
$item->expiresAt(
    null,
);
```

or:

```php
$item->expiresAfter(
    null,
);
```

## Checking an Item

The pool can check whether a key currently exists:

```php
if ($pool->hasItem('user.42')) {
    // Cache exists.
}
```

You can also inspect an item:

```php
$item = $pool->getItem(
    'user.42',
);

if ($item->isHit()) {
    $value = $item->get();
}
```

## Multiple Items

Several items can be retrieved at once:

```php
$items = $pool->getItems([
    'user.1',
    'user.2',
    'user.3',
]);
```

Items are indexed by their cache keys:

```php
foreach ($items as $key => $item) {
    if ($item->isHit()) {
        $value = $item->get();
    }
}
```

A missing key still produces a cache item with:

```php
$item->isHit() === false;
```

## Deleting Items

Delete a single item:

```php
$pool->deleteItem(
    'user.42',
);
```

Delete several items:

```php
$pool->deleteItems([
    'user.1',
    'user.2',
    'user.3',
]);
```

Clear the pool:

```php
$pool->clear();
```

## Deferred Saves

PSR-6 supports deferred cache writes.

Create an item:

```php
$item = $pool->getItem(
    'user.42',
);

$item->set([
    'id' => 42,
    'name' => 'Noga',
]);
```

Queue it without immediately persisting it:

```php
$pool->saveDeferred(
    $item,
);
```

The item remains queued inside the pool.

Persist all queued items with:

```php
$pool->commit();
```

Multiple items can be deferred:

```php
$user1 = $pool->getItem(
    'user.1',
);

$user1->set([
    'id' => 1,
]);

$user2 = $pool->getItem(
    'user.2',
);

$user2->set([
    'id' => 2,
]);

$pool->saveDeferred(
    $user1,
);

$pool->saveDeferred(
    $user2,
);

$pool->commit();
```

The flow is:

```text
PsrCacheItem
      |
      v
saveDeferred()
      |
      v
Deferred Queue
      |
      v
commit()
      |
      v
NCache
      |
      v
Cache Driver
```

## Cache Profiles

Each pool is associated with an NCache configuration profile:

```php
$users = new CacheItemPool(
    $config,
    'users',
);

$admin = new CacheItemPool(
    $config,
    'admin',
);
```

The appropriate profile is activated before cache operations.

NCache includes the profile in its internal cache identity, allowing identical keys to remain isolated between profiles even when they use the same cache directory.

For example:

```text
users + user.42
admin + user.42
```

represent different cache entries.

## Cache Keys

PSR-6 keys must be valid strings.

Empty keys are rejected:

```php
$pool->getItem('');
```

Reserved characters are also rejected:

```text
{ } ( ) / \ @ :
```

For example:

```php
$pool->getItem(
    'user:42',
);
```

throws an invalid cache argument exception.

## Supported Drivers

The PSR-6 adapter uses the same drivers as NCache:

| Driver | CType |
| --- | --- |
| JSON | `CType::JSON` |
| Serialized PHP | `CType::SERIALIZE` |
| PHP Array | `CType::ARRAY_PHP` |
| String | `CType::STRING` |
| SQLite | `CType::SQLite` |
| Redis | `CType::REDIS` |
| Memcached | `CType::MEMCACHED` |

Driver availability depends on the corresponding PHP extensions and NCache configuration.

## PSR-6 API

`CacheItemPool` implements the complete `CacheItemPoolInterface` API:

| Method | Description |
| --- | --- |
| `getItem()` | Retrieve one cache item |
| `getItems()` | Retrieve multiple cache items |
| `hasItem()` | Check whether an item exists |
| `clear()` | Clear cache entries |
| `deleteItem()` | Delete one item |
| `deleteItems()` | Delete multiple items |
| `save()` | Persist an item immediately |
| `saveDeferred()` | Queue an item for later persistence |
| `commit()` | Persist queued items |

`PsrCacheItem` implements `CacheItemInterface`:

| Method | Description |
| --- | --- |
| `getKey()` | Return the cache key |
| `get()` | Return the stored value |
| `isHit()` | Determine whether the item was found |
| `set()` | Set the value in memory |
| `expiresAt()` | Set an absolute expiration |
| `expiresAfter()` | Set a relative expiration |

## PSR-6 vs PSR-16

NCache supports both PSR caching APIs.

PSR-16 provides a simpler direct interface:

```php
$cache->set(
    'user.42',
    $user,
    3600,
);

$user = $cache->get(
    'user.42',
);
```

PSR-6 uses cache items and pools:

```php
$item = $pool->getItem(
    'user.42',
);

$item
    ->set($user)
    ->expiresAfter(
        3600,
    );

$pool->save(
    $item,
);
```

Use PSR-16 when a simple key/value cache API is sufficient.

Use PSR-6 when an application or library expects cache items, cache pools, or deferred persistence.

## Native NCache API

The PSR-6 adapter intentionally exposes only the standardized PSR-6 API.

NCache-specific functionality remains available through the native API.

For example:

```php
NCache::key('articles')
    ->tags('content')
    ->signature('articles-v2')
    ->set($articles)
    ->put();
```

Features such as tags, signatures, explicit per-operation drivers, and other NCache extensions are not added to the PSR interfaces.

This keeps NCache interoperable with PSR-compatible libraries without coupling the native API to PSR-6.

## Testing

The PSR-6 integration is tested for:

- cache hits and misses
- storing and retrieving items
- multiple item retrieval
- item existence
- single deletion
- multiple deletion
- cache clearing
- integer TTL
- expired items
- deferred persistence
- deferred commit
- invalid cache keys
- PSR cache item state and expiration

The PSR-6 adapter is tested across the PHP versions supported by NCache.

## Example

```php
<?php

declare(strict_types=1);

use NCache\Enum\CType;
use NCache\Psr\PsrCache\CacheItemPool;

require __DIR__ . '/vendor/autoload.php';

$pool = new CacheItemPool(
    __DIR__ . '/ncache.config.json',
    'users',
    CType::JSON,
);

$item = $pool->getItem(
    'user.42',
);

if (!$item->isHit()) {
    $item
        ->set([
            'id' => 42,
            'name' => 'Noga',
        ])
        ->expiresAfter(
            3600,
        );

    $pool->save(
        $item,
    );
}

$user = $item->get();
```
