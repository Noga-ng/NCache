# NCache PSR-16 Adapter

NCache provides a PSR-16 compatible adapter through `SimpleCache`.

The adapter implements:

```php
Psr\SimpleCache\CacheInterface
```

It allows applications and libraries expecting a PSR-16 cache implementation to use NCache while keeping the native NCache API independent.

## Requirements

- PHP >= 8.1
- NCache
- `psr/simple-cache ^3.0`

Install the PSR interface with Composer:

```bash
composer require psr/simple-cache:^3.0
```

## Basic Usage

Create a PSR-16 cache instance:

```php
<?php

use NCache\Psr\SimpleCache\SimpleCache;

$cache = new SimpleCache(
    __DIR__ . '/ncache.config.json',
);
```

The `default` NCache profile is used when no profile is explicitly provided.

Store a value:

```php
$cache->set(
    'user.42',
    [
        'id' => 42,
        'name' => 'Noga',
    ],
);
```

Retrieve it:

```php
$user = $cache->get(
    'user.42',
);
```

Provide a fallback value:

```php
$user = $cache->get(
    'user.42',
    [],
);
```

Check whether a cache entry exists:

```php
if ($cache->has('user.42')) {
    // Cache exists and is valid.
}
```

Delete an entry:

```php
$cache->delete(
    'user.42',
);
```

Clear the cache:

```php
$cache->clear();
```

## Configuration Profile

A specific NCache profile can be selected when creating the adapter:

```php
$cache = new SimpleCache(
    __DIR__ . '/ncache.config.json',
    'users',
);
```

The profile is automatically activated before cache operations.

This allows multiple PSR-16 adapters to coexist:

```php
$users = new SimpleCache(
    __DIR__ . '/ncache.config.json',
    'users',
);

$admin = new SimpleCache(
    __DIR__ . '/ncache.config.json',
    'admin',
);
```

Each adapter keeps its configured profile.

NCache includes the profile in the internal cache identity, preventing identical keys from colliding between different profiles.

## Selecting a Driver

A driver can optionally be fixed when creating the adapter:

```php
use NCache\Enum\CType;
use NCache\Psr\SimpleCache\SimpleCache;

$cache = new SimpleCache(
    __DIR__ . '/ncache.config.json',
    'users',
    CType::REDIS,
);
```

All operations performed by this adapter will use the selected driver.

If no driver is provided:

```php
$cache = new SimpleCache(
    __DIR__ . '/ncache.config.json',
    'users',
);
```

NCache uses the `defaultDriver` configured for the selected profile.

Unlike the native NCache API, the PSR-16 adapter does not provide runtime driver switching.

Create another adapter when a different driver is required:

```php
$redis = new SimpleCache(
    $config,
    'users',
    CType::REDIS,
);

$json = new SimpleCache(
    $config,
    'users',
    CType::JSON,
);
```

## TTL

PSR-16 supports integer TTL values:

```php
$cache->set(
    'session.42',
    $session,
    3600,
);
```

The value is expressed in seconds.

`DateInterval` is also supported:

```php
use DateInterval;

$cache->set(
    'session.42',
    $session,
    new DateInterval('PT1H'),
);
```

A TTL equal to or lower than zero causes the cache entry to be removed:

```php
$cache->set(
    'session.42',
    $session,
    0,
);
```

When `$ttl` is `null`, no explicit PSR-16 TTL is applied by the adapter.

## Multiple Operations

### Get Multiple

```php
$values = $cache->getMultiple([
    'user.1',
    'user.2',
    'user.3',
]);
```

A default value can be provided for missing entries:

```php
$values = $cache->getMultiple(
    [
        'user.1',
        'user.2',
    ],
    [],
);
```

### Set Multiple

```php
$cache->setMultiple([
    'user.1' => [
        'id' => 1,
    ],
    'user.2' => [
        'id' => 2,
    ],
]);
```

A common TTL can be applied:

```php
$cache->setMultiple(
    [
        'user.1' => ['id' => 1],
        'user.2' => ['id' => 2],
    ],
    3600,
);
```

### Delete Multiple

```php
$cache->deleteMultiple([
    'user.1',
    'user.2',
]);
```

## Supported Values

The PSR-16 adapter currently accepts the data types supported by NCache:

```text
array
string
integer
float
boolean
null
```

Nested arrays may contain mixed values.

Unsupported values cause an `InvalidCacheArgumentException`.

## Cache Keys

PSR-16 cache keys must be valid strings.

Empty keys are rejected:

```php
$cache->get('');
```

Reserved PSR-16 characters are also rejected:

```text
{ } ( ) / \ @ :
```

For example:

```php
$cache->get('user:42');
```

throws an `InvalidCacheArgumentException`.

## PSR-16 API

The adapter implements the complete `CacheInterface` contract:

| Method | Description |
| --- | --- |
| `get()` | Retrieve a cached value |
| `set()` | Store a value |
| `delete()` | Delete one cache entry |
| `clear()` | Clear cache entries |
| `getMultiple()` | Retrieve multiple entries |
| `setMultiple()` | Store multiple entries |
| `deleteMultiple()` | Delete multiple entries |
| `has()` | Check whether an entry exists |

## Native NCache vs PSR-16

The PSR-16 adapter intentionally exposes only the standardized Simple Cache API.

Native NCache features remain available through `NCache` itself.

For example:

```php
NCache::key('articles')
    ->tags('content')
    ->signature('articles-v2')
    ->set($articles)
    ->put();
```

Features such as tags, signatures, namespaces, explicit driver selection per operation, and other NCache-specific capabilities are not added to `CacheInterface`.

This keeps the adapter compatible with PSR-16 while preserving the full NCache API separately.

## Architecture

```text
Application / Framework
        |
        v
Psr\SimpleCache\CacheInterface
        |
        v
NCache\Psr\SimpleCache\SimpleCache
        |
        v
       NCache
        |
        v
Driver Registry
        |
        +-- JSON
        +-- SERIALIZE
        +-- ARRAY_PHP
        +-- STRING
        +-- SQLite
        +-- Redis
        +-- Memcached
```

The PSR-16 layer acts as an adapter. It does not replace or modify the native NCache API.

## Example

```php
<?php

declare(strict_types=1);

use NCache\Enum\CType;
use NCache\Psr\SimpleCache\SimpleCache;

require __DIR__ . '/vendor/autoload.php';

$cache = new SimpleCache(
    __DIR__ . '/ncache.config.json',
    'users',
    CType::JSON,
);

$cache->set(
    'user.42',
    [
        'id' => 42,
        'name' => 'Noga',
    ],
    3600,
);

if ($cache->has('user.42')) {
    $user = $cache->get(
        'user.42',
    );
}

$cache->delete(
    'user.42',
);
```

## Testing

The PSR-16 adapter is covered by tests for:

- basic `get`, `set`, `has`, and `delete`
- default values
- integer TTL
- `DateInterval`
- zero and negative TTL
- multiple operations
- cache clearing
- invalid keys
- unsupported values
- multiple adapters using different NCache profiles

The adapter is tested across the PHP versions supported by NCache.
