# NCache

A lightweight, multi-driver caching library for PHP 8.1+.

NCache provides a unified API for file-based, SQLite, Redis, and Memcached caches while keeping profiles, TTL, namespaces, signatures, tags, metadata, and cache clearing consistent across drivers.

## Features

- PHP 8.1+
- JSON cache
- Serialized PHP cache
- String cache
- PHP array cache (`ARRAY_PHP`) with OPcache-friendly files
- SQLite cache
- Redis cache
- Memcached cache
- Configurable default driver
- Multiple isolated cache profiles
- Immutable resolved configuration state per cache instance
- Relative cache paths resolved from the configuration file
- Cache namespaces and scoped directories
- Persistent cache, default TTL, and explicit TTL
- Human-readable TTL expressions
- Shared Redis/Memcached configuration with `driversFrom`
- Redis database selection
- Cache signatures
- Cache tags (group invalidation)
- Cache registry with metadata
- Transactional registry updates using file locking
- Atomic file replacement for file-based cache writes
- Per-driver, scoped, and tag-based cache clearing

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

When `NCache::key()` creates a cache instance, NCache captures the resolved state of the currently selected profile.

Changing the active profile later does not mutate cache instances that were already created.

```php
$config = NCache::config(
    __DIR__ . '/ncache.config.json'
);

$config->use('users');

$userCache = NCache::key('user.42');

$config->use('admin');

$adminCache = NCache::key('dashboard');
```

`$userCache` keeps the resolved `users` configuration, while `$adminCache` uses `admin`.

```text
$userCache  -> users
$adminCache -> admin
```

A later call to `use()` does not switch the configuration of either existing cache instance.

## Relative Cache Paths

Relative `cachePath` values are resolved from the directory containing the configuration file, not from the application's current working directory.

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

`CType::ARRAY_PHP` stores an array as a PHP file that returns the cached value.

```php
NCache::key(
    'routes',
    CType::ARRAY_PHP
)
    ->set([
        'home'  => '/',
        'users' => '/users',
    ])
    ->put();
```

The generated cache is equivalent to:

```php
<?php

return [
    'home'  => '/',
    'users' => '/users',
];
```

This driver can benefit from PHP OPcache because the cache is loaded through PHP itself rather than decoded from JSON or unserialized at runtime.

When using this driver, configure its extension:

```json
{
    "extensions": {
        "ARRAY_PHP": "php"
    }
}
```

## TTL

NCache distinguishes between persistent cache, configured default TTL, and explicit TTL.

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

For file-based drivers, the scope is resolved below the configured `cachePath`.

This allows identical keys to remain isolated between different profiles or cache scopes.

## Shared Driver Configuration

`driversFrom` allows a profile to reuse Redis and Memcached connection settings from another profile.

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

The inherited driver configuration is resolved into the selected profile state.

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

## Cache Signatures

A signature associates cached data with the state or version of another resource.

```php
NCache::key('users')
    ->signature('users-v2')
    ->set($users)
    ->put();
```

Validate it later:

```php
$valid = NCache::key('users')
    ->hasValidSignature('users-v2');
```

For file-based cache entries, registry metadata such as stored file size can also be used as an additional consistency check.

## Cache Tags

Tags group multiple cache entries under a shared label so they can be invalidated together, regardless of driver or key.

Tag a cache entry when storing:

```php
NCache::key('article.1')
    ->tag('article')
    ->set($article)
    ->put();

NCache::key('articles.recent')
    ->tag(['article', 'homepage'])
    ->set($list)
    ->put();
```

Invalidate every entry that carries a given tag:

```php
NCache::invalidateTag('article');
```

Tag invalidation works across all drivers. For file-based caches, the underlying cache file is removed in addition to the registry entry.

## Append Data

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

Registry mutations use an exclusive lock around the complete read-modify-write cycle, reducing lost updates when multiple processes modify the same registry concurrently.

## Atomic File Writes

File-based drivers write cache data through a temporary file and replace the final target only after the write is complete.

This avoids exposing partially written cache files during normal writes.

The registry uses a separate stable lock file so the registry file itself can still be replaced atomically.

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

Clear by tag:

```php
NCache::invalidateTag('article');
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

## Quality

NCache is developed and tested with:

- PHPUnit
- PHPStan level 9
- PHP CS Fixer
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
