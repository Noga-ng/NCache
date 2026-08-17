# NCache

[![CI](https://github.com/Noga-ng/NCache/actions/workflows/ci.yml/badge.svg)](https://github.com/Noga-ng/NCache/actions/workflows/ci.yml)
![PHP](https://img.shields.io/badge/PHP-8.1%20--%208.5-777BB4)
![PHPStan](https://img.shields.io/badge/PHPStan-level%209-blue)
![PHPUnit](https://img.shields.io/badge/tested%20with-PHPUnit-blue)
![PHP-CS-Fixer](https://img.shields.io/badge/code%20style-PHP--CS--Fixer-blue)
![Version](https://img.shields.io/badge/version-1.1.0-blue)

A lightweight, multi-driver caching library for PHP 8.1+.

NCache provides a unified caching API for file-based storage, SQLite, Redis,
and Memcached while keeping profiles, TTL, namespaces, signatures, tags,
metadata, and cache invalidation consistent across drivers.

NCache also provides PSR-6 and PSR-16 adapters for applications and libraries
that rely on standard PHP-FIG caching interfaces.

## Features

- PHP 8.1+
- Unified multi-driver cache API
- PSR-6 `CacheItemInterface` and `CacheItemPoolInterface`
- PSR-16 `CacheInterface`
- JSON cache
- Serialized PHP cache
- String cache
- PHP array cache (`ARRAY_PHP`) with OPcache-friendly files
- SQLite cache
- Redis cache
- Memcached cache
- Configurable default driver
- Multiple isolated cache profiles
- Profile-aware cache key isolation
- Immutable resolved configuration state per cache instance
- Relative cache paths resolved from the configuration file
- Cache namespaces and scoped directories
- Persistent cache, default TTL, and explicit TTL
- Human-readable TTL expressions
- Shared Redis/Memcached configuration with `driversFrom`
- Redis database selection
- Cache signatures
- Callable values and signatures
- Cache tags and global tag invalidation
- Lazy deletion of tag-invalidated entries
- Cache registry with metadata
- Transactional registry updates using file locking
- Atomic file replacement for file-based cache writes
- Lazy recursive cache-directory iteration
- Per-driver and scoped cache clearing

## Requirements

- PHP >= 8.1
- Composer

Depending on the selected driver:

- `ext-redis` for Redis
- `ext-memcached` for Memcached
- `ext-pdo_sqlite` for SQLite

## Installation

```bash
composer require noga-ng/ncache
```

## Quick Start

Create a configuration file:

```json
{
    "default": {
        "cachePath": "./cache",
        "defaultDriver": "JSON",
        "namespace": "default",
        "extensions": {
            "JSON": "json",
            "SERIALIZE": "nc",
            "STRING": "txt",
            "ARRAY_PHP": "php"
        },
        "defaultTtl": "hours(1)"
    }
}
```

Load the configuration and select a profile:

```php
<?php

use NCache\NCache;

NCache::config(
    __DIR__ . '/ncache.config.json'
)->use('default');
```

Store a value:

```php
NCache::key('user.42')
    ->set([
        'id' => 42,
        'name' => 'Noga',
    ])
    ->put();
```

Retrieve it:

```php
$user = NCache::key('user.42')->get();
```

Check whether it exists:

```php
if (NCache::key('user.42')->has()) {
    // Cache exists and is still valid.
}
```

Delete it:

```php
NCache::key('user.42')->delete();
```

## Callable Values

NCache supports callables for dynamically resolved cache values.

Instead of resolving a value before passing it to NCache:

```php
$users = loadUsers();

NCache::key('users')
    ->set($users)
    ->put();
```

a callable can be passed directly:

```php
NCache::key('users')
    ->set(
        fn () => loadUsers()
    )
    ->put();
```

Callables are also supported by `append()`:

```php
NCache::key('users')
    ->append(
        fn () => loadMoreUsers()
    )
    ->put();
```

Signatures can also be dynamically resolved:

```php
NCache::key('users')
    ->signature(
        fn () => getUsersVersion()
    )
    ->set(
        fn () => loadUsers()
    )
    ->put();
```

and validated against the current resource state:

```php
$valid = NCache::key('users')
    ->hasValidSignature(
        fn () => getUsersVersion()
    );
```

Callable support is part of the native NCache API and is also available when
values pass through the PSR adapters.

## PSR Compatibility

NCache provides compatibility adapters for the standard PHP-FIG caching
interfaces without coupling the native API to PSR-specific behavior.

### PSR-16 — Simple Cache

NCache implements `Psr\SimpleCache\CacheInterface` through the `SimpleCache`
adapter.

```php
use NCache\Psr\SimpleCache\SimpleCache;

$cache = new SimpleCache(
    __DIR__ . '/ncache.config.json',
    'default',
);

$cache->set(
    'user.42',
    ['id' => 42],
    3600,
);

$user = $cache->get(
    'user.42',
);
```

PSR-16 provides a portable key/value caching API with TTL, multiple operations,
deletion, and cache clearing.

**[Read the PSR-16 documentation](Src/Psr/SimpleCache/README.md)**

### PSR-6 — Caching Interface

NCache implements:

- `Psr\Cache\CacheItemInterface`
- `Psr\Cache\CacheItemPoolInterface`

Create a cache pool:

```php
use NCache\Psr\PsrCache\CacheItemPool;

$pool = new CacheItemPool(
    __DIR__ . '/ncache.config.json',
    'default',
);
```

Retrieve and store an item:

```php
$item = $pool->getItem(
    'user.42',
);

if (!$item->isHit()) {
    $item
        ->set([
            'id' => 42,
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

PSR-6 also supports deferred persistence:

```php
$item = $pool->getItem(
    'user.42',
);

$item->set([
    'id' => 42,
]);

$pool->saveDeferred(
    $item,
);

$pool->commit();
```

**[Read the PSR-6 documentation](Src/Psr/PsrCache/README.md)**

### Which API Should I Use?

| API | Recommended for |
| --- | --- |
| Native NCache | Full NCache functionality including tags, signatures, namespaces, callables, and driver control |
| PSR-16 | Simple and portable key/value caching |
| PSR-6 | Libraries and frameworks requiring cache items and cache pools |

The PSR adapters are compatibility layers. They do not replace or restrict
the native NCache API.

## Configuration

NCache uses a JSON configuration file containing one or more cache profiles.

```json
{
    "shared": {
        "cachePath": "./cache/shared",
        "defaultDriver": "SERIALIZE",
        "namespace": "shared",
        "extensions": {
            "SERIALIZE": "nc",
            "STRING": "txt",
            "ARRAY_PHP": "php"
        },
        "defaultTtl": "days(2)",
        "drivers": {
            "redis": {
                "host": "127.0.0.1",
                "port": 6379,
                "timeout": 0,
                "password": null,
                "database": 0
            },
            "memcached": {
                "host": "127.0.0.1",
                "port": 11211,
                "weight": 0
            }
        }
    },
    "admin": {
        "cachePath": "./cache/admin",
        "defaultDriver": "SERIALIZE",
        "namespace": "admin",
        "extensions": {
            "SERIALIZE": "nc",
            "STRING": "txt"
        },
        "defaultTtl": "minutes(15)",
        "driversFrom": "shared"
    },
    "users": {
        "cachePath": "./storage/users",
        "defaultDriver": "REDIS",
        "namespace": "users",
        "extensions": {},
        "defaultTtl": "hours(1)",
        "driversFrom": "shared"
    },
    "client": {
        "cachePath": "./clients",
        "defaultDriver": "JSON",
        "namespace": "client",
        "extensions": {
            "JSON": "json"
        },
        "defaultTtl": "make(1,10,15,25)",
        "driversFrom": "shared"
    }
}
```

Select a profile:

```php
NCache::config(
    __DIR__ . '/ncache.config.json'
)->use('users');
```

## Profile Isolation

When `NCache::key()` creates a cache instance, NCache captures the resolved
state of the currently selected profile.

Changing the active profile later does not mutate cache instances that were
already created.

```php
$config = NCache::config(
    __DIR__ . '/ncache.config.json'
);

$config->use('users');

$userCache = NCache::key(
    'user.42'
);

$config->use('admin');

$adminCache = NCache::key(
    'dashboard'
);
```

The resulting instances remain independent:

```text
$userCache  -> users
$adminCache -> admin
```

A later call to `use()` does not switch the configuration of either existing
cache instance.

Cache identity also includes the active profile, preventing identical cache
keys from colliding when multiple profiles share the same cache directory.

## Relative Cache Paths

Relative `cachePath` values are resolved from the directory containing the
configuration file, not from the application's current working directory.

Example:

```text
/project
├── config
│   └── ncache.config.json
└── cache
```

With:

```json
{
    "cachePath": "../cache"
}
```

NCache resolves the path relative to `config/ncache.config.json`.

## Cache Drivers

A profile can define its default driver:

```json
{
    "defaultDriver": "JSON"
}
```

The driver can then be omitted:

```php
NCache::key('users')
    ->set($users)
    ->put();
```

A driver can also be selected explicitly:

```php
use NCache\Enum\CType;
use NCache\NCache;

NCache::key(
    'users',
    CType::REDIS
)
    ->set($users)
    ->put();
```

An explicitly provided driver takes precedence over `defaultDriver`.

## PHP Array Cache

`CType::ARRAY_PHP` stores cache data as a PHP file returning an array.

```php
NCache::key(
    'routes',
    CType::ARRAY_PHP
)
    ->set([
        'home' => '/',
        'users' => '/users',
    ])
    ->put();
```

The generated cache is equivalent to:

```php
<?php

return [
    'home' => '/',
    'users' => '/users',
];
```

Because the generated cache is loaded as PHP, this driver can benefit from
PHP OPcache instead of requiring JSON decoding or unserialization for each
read.

Configure the extension with:

```json
{
    "extensions": {
        "ARRAY_PHP": "php"
    }
}
```

## TTL

NCache distinguishes between persistent cache, configured default TTL, and
explicit TTL.

### Persistent Cache

If `ttl()` is not called:

```php
NCache::key('settings')
    ->set($settings)
    ->put();
```

the cache has no expiration.

### Default TTL

Calling `ttl()` without a value uses the active profile's `defaultTtl`:

```php
NCache::key('users')
    ->ttl()
    ->set($users)
    ->put();
```

### Explicit TTL

Passing a TTL explicitly overrides the configured default:

```php
NCache::key('users')
    ->ttl(3600)
    ->set($users)
    ->put();
```

### Human-readable TTL

Configuration files support duration expressions:

```text
month(...)
week(...)
days(...)
hours(...)
minutes(...)
second(...)
make(days, hours, minutes, seconds)
```

Examples:

```json
{
    "defaultTtl": "days(2)"
}
```

```json
{
    "defaultTtl": "make(1,10,15,25)"
}
```

A numeric TTL can also be used:

```json
{
    "defaultTtl": 3600
}
```

Use `null` when no default TTL should be defined.

## Namespaces and Scopes

Each profile can define a namespace:

```json
{
    "namespace": "users"
}
```

A cache operation can also use an additional directory or scope:

```php
NCache::key('profile')
    ->dir('api')
    ->set($data)
    ->put();
```

For file-based drivers, the scope is resolved below the configured
`cachePath`.

This allows identical keys to remain isolated between different profiles or
cache scopes.

## Shared Driver Configuration

`driversFrom` allows a profile to reuse Redis and Memcached connection
settings from another profile.

```json
{
    "shared": {
        "cachePath": "./cache/shared",
        "defaultDriver": "SERIALIZE",
        "namespace": "shared",
        "extensions": {},
        "defaultTtl": null,
        "drivers": {
            "redis": {
                "host": "127.0.0.1",
                "port": 6379,
                "timeout": 0,
                "password": null,
                "database": 0
            },
            "memcached": {
                "host": "127.0.0.1",
                "port": 11211,
                "weight": 0
            }
        }
    },
    "users": {
        "cachePath": "./cache/users",
        "defaultDriver": "REDIS",
        "namespace": "users",
        "extensions": {},
        "defaultTtl": "hours(1)",
        "driversFrom": "shared"
    }
}
```

The inherited driver configuration is resolved into the selected profile
state.

## Redis

Redis configuration is defined in `drivers.redis`:

```json
{
    "redis": {
        "host": "127.0.0.1",
        "port": 6379,
        "timeout": 0,
        "password": null,
        "database": 0
    }
}
```

The `database` option selects the Redis database used by the connection.

## Memcached

Memcached configuration is defined in `drivers.memcached`:

```json
{
    "memcached": {
        "host": "127.0.0.1",
        "port": 11211,
        "weight": 0
    }
}
```

## Cache Tags

Tags allow multiple cache entries to be grouped logically and invalidated
together without depending on a specific cache driver.

Attach one tag:

```php
NCache::key('user.42')
    ->tags('users')
    ->set($user)
    ->put();
```

Attach multiple tags:

```php
NCache::key('article.42')
    ->tags([
        'articles',
        'homepage',
    ])
    ->set($article)
    ->put();
```

The registry stores tag metadata with the cache entry:

```php
[
    'state' => true,
    'entries' => [
        'articles',
        'homepage',
    ],
]
```

### Tag Invalidation

Invalidate every cache entry associated with a tag:

```php
NCache::invalidateTag(
    'articles'
);
```

Tag invalidation is intentionally lazy.

`invalidateTag()` does not immediately delete the underlying cached value.
Matching registry entries are marked as invalid:

```php
[
    'state' => false,
    'entries' => [
        'articles',
        'homepage',
    ],
]
```

The next `get()` or `has()` detects the invalid tag state, deletes the cached
value through its own driver, and removes the corresponding registry entry.

```text
invalidateTag()
      |
      v
registry: tags.state = false
      |
      | no immediate backend deletion
      v
get() / has()
      |
      v
lazy delete
      |
      +-- cached value
      +-- registry entry
```

This keeps tag invalidation independent from the storage backend and gives
file caches, SQLite, Redis, and Memcached the same invalidation lifecycle.

Tags are intentionally separate from TTL and signatures:

- **TTL** controls time-based expiration.
- **Tags** provide explicit group invalidation.
- **Signatures** validate cached data against the state of another resource.

## Cache Signatures

A signature associates cached data with the state or version of another
resource.

```php
NCache::key('users')
    ->signature(
        'users-v2'
    )
    ->set($users)
    ->put();
```

Validate it later:

```php
$valid = NCache::key('users')
    ->hasValidSignature(
        'users-v2'
    );
```

Signatures also support callables:

```php
NCache::key('users')
    ->signature(
        fn () => getUsersVersion()
    )
    ->set($users)
    ->put();
```

The current state can then be resolved dynamically during validation:

```php
$valid = NCache::key('users')
    ->hasValidSignature(
        fn () => getUsersVersion()
    );
```

For file-based cache entries, registry metadata such as stored file size can
also be used as an additional consistency check.

## Append Data

Append data to an existing cache value:

```php
NCache::key('users')
    ->set([
        ['id' => 1],
    ])
    ->append([
        ['id' => 2],
    ])
    ->put();
```

`append()` also accepts a callable:

```php
NCache::key('users')
    ->append(
        fn () => [
            ['id' => 3],
        ]
    )
    ->put();
```

## TTL Information

Retrieve the remaining TTL:

```php
$remaining = NCache::key('users')
    ->ttlRemaining();
```

Inspect the TTL state:

```php
$state = NCache::key('users')
    ->ttlState();
```

## Registry

NCache maintains cache metadata independently from the cached value.

```php
$registry = NCache::key('users')
    ->getRegistry();
```

Registry metadata includes:

- cache type
- original cache name
- internal hashed key
- namespace
- associated file
- file size when applicable
- signature
- tags
- TTL
- expiration timestamp

Registry mutations use an exclusive lock around the complete
read-modify-write cycle, reducing lost updates when multiple processes modify
the same registry concurrently.

## Atomic File Writes

File-based drivers write cache data through a temporary file and replace the
final target only after the write is complete.

This avoids exposing partially written cache files during normal writes.

The registry uses a separate stable lock file so the registry file itself can
still be replaced atomically.

## Lazy Directory Iteration

Recursive cache-directory traversal is performed lazily using PHP iterators
and generators.

Entries are yielded progressively instead of first collecting the entire
directory tree into an in-memory array.

This keeps directory operations more memory-efficient when working with a
large number of cache files.

## Clearing Cache

Clear caches for the default driver:

```php
NCache::clear();
```

Clear a specific driver:

```php
NCache::clear(
    type: CType::JSON
);
```

Clear a specific scope:

```php
NCache::clear(
    dir: 'users',
    type: CType::JSON
);
```

## Supported Drivers

| Driver | CType |
| --- | --- |
| JSON | `CType::JSON` |
| Serialized PHP | `CType::SERIALIZE` |
| String | `CType::STRING` |
| PHP Array | `CType::ARRAY_PHP` |
| SQLite | `CType::SQLite` |
| Redis | `CType::REDIS` |
| Memcached | `CType::MEMCACHED` |

## What's New in v1.1.0

NCache 1.1 expands interoperability, cache invalidation, and native API
ergonomics while keeping the existing multi-driver architecture.

### PSR

- Added PSR-16 Simple Cache support
- Added PSR-6 Cache Item Pool support
- Added PSR-6 expiration management
- Added PSR-6 deferred persistence with `saveDeferred()` and `commit()`
- Added dedicated PSR-6 and PSR-16 documentation

### Cache API

- Added callable support for cache values
- Added callable support to `append()`
- Added callable-based signatures and signature validation
- Added cache tags
- Added global lazy tag invalidation
- Improved profile-aware cache key isolation

### Storage

- Added the `ARRAY_PHP` cache driver
- Added OPcache-friendly PHP array cache files
- Improved lazy recursive directory iteration

### Quality

- PHPStan level 9
- PHPUnit coverage for native, PSR-6, and PSR-16 APIs
- PHP-CS-Fixer validation
- GitHub Actions CI across PHP 8.1–8.5

NCache is developed and tested with:

- PHPUnit
- PHPStan level 9
- PHP-CS-Fixer
- GitHub Actions

Continuous integration validates NCache on:

- PHP 8.1
- PHP 8.2
- PHP 8.3
- PHP 8.4
- PHP 8.5

The CI suite also validates Redis and Memcached integration.

## License

NCache is released under the MIT License.
