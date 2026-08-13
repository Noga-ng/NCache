# NCache

A lightweight and flexible caching library for PHP 8.1+.

NCache provides a unified API for multiple cache backends while keeping configuration, expiration, namespaces, signatures, and cache metadata consistent across drivers.

## Features

- PHP 8.1+
- JSON cache
- Serialized PHP cache
- String cache
- SQLite cache
- Redis cache
- Memcached cache
- Configurable default driver
- Multiple cache profiles
- Cache namespaces
- TTL and default TTL
- Human-readable TTL configuration
- Shared driver configuration with `driversFrom`
- Cache signatures
- Cache registry
- Per-driver and per-namespace cache clearing
- Redis database selection

## Requirements

- PHP >= 8.1
- Composer

Depending on the driver used:

- Redis extension for Redis
- Memcached extension for Memcached
- PDO SQLite for SQLite

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
            "SERIALIZE": "nc",
            "STRING": "txt"
        },
        "defaultTtl": "hours(1)"
    }
}
```

Load the configuration:

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

## Configuration

NCache uses JSON configuration files containing one or more cache profiles.

```json
{
    "shared": {
        "cachePath": "./cache/shared",
        "defaultDriver": "SERIALIZE",
        "namespace": "default",
        "extensions": {
            "SERIALIZE": "nc",
            "STRING": "txt"
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
                "timeout": 0
            }
        }
    },

    "admin": {
        "cachePath": "./cache",
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

Select the profile you want to use:

```php
NCache::config(
    __DIR__ . '/ncache.config.json'
)->use('users');
```

### Relative Cache Paths

Relative `cachePath` values are resolved from the directory containing the configuration file.

For example:

```text
/project
├── config
│   └── ncache.config.json
└── cache
```

Using:

```json
{
    "cachePath": "../cache"
}
```

the cache directory is resolved relative to `config/ncache.config.json`.

This allows each configuration file to define its own cache location independently from the application's current working directory.

## Cache Drivers

A default driver can be defined directly in a profile:

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

Calling `ttl()` without a value uses the `defaultTtl` defined by the active configuration profile:

```php
NCache::key('users')
    ->ttl()
    ->set($users)
    ->put();
```

For example:

```json
{
    "defaultTtl": "hours(1)"
}
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

Configuration files support human-readable duration expressions.

```json
{
    "defaultTtl": "minutes(15)"
}
```

Supported forms include:

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

A numeric TTL can also be used directly.

```json
{
    "defaultTtl": 3600
}
```

Set it to `null` when no default expiration should be defined.

## Namespaces

Each profile can define its own namespace:

```json
{
    "namespace": "users"
}
```

A namespace can also be selected for a specific cache operation:

```php
NCache::key('profile')
    ->dir('api')
    ->set($data)
    ->put();
```

This allows identical cache keys to remain isolated between different cache scopes.

## Shared Driver Configuration

Multiple profiles often use the same Redis or Memcached server.

`driversFrom` allows a profile to inherit driver connection settings from another profile instead of duplicating them.

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

This keeps connection configuration centralized while allowing each profile to keep its own cache path, namespace, default driver, and TTL.

## Redis

Redis configuration can be defined inside the `drivers` section:

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

Usage:

```php
NCache::key(
    'users',
    CType::REDIS
)
    ->set($users)
    ->put();
```

## Memcached

Memcached configuration can also be defined inside `drivers`:

```json
{
    "memcached": {
        "host": "127.0.0.1",
        "port": 11211,
        "timeout": 0
    }
}
```

Usage:

```php
NCache::key(
    'users',
    CType::MEMCACHED
)
    ->set($users)
    ->put();
```

## Cache Signatures

A signature associates cached data with the state or version of another resource.

```php
NCache::key('users')
    ->signature('users-v2')
    ->set($users)
    ->put();
```

The signature can later be checked:

```php
$valid = NCache::key('users')
    ->hasValidSignature('users-v2');
```

This is useful when cached data should only remain valid while another resource keeps the same state.

## Append Data

Data can be appended before writing the cache:

```php
NCache::key('users')
    ->set([
        ['id' => 1]
    ])
    ->append([
        ['id' => 2]
    ])
    ->put();
```

## TTL Information

The remaining lifetime of a cache entry can be retrieved with:

```php
$remaining = NCache::key('users')
    ->ttlRemaining();
```

Its TTL state can also be inspected:

```php
$state = NCache::key('users')
    ->ttlState();
```

## Registry

NCache maintains cache metadata independently from the stored value.

Registry information can be retrieved with:

```php
$registry = NCache::key('users')
    ->getRegistry();
```

The registry contains metadata such as:

- cache type
- original cache name
- internal hashed key
- associated file
- signature
- TTL
- expiration timestamp

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

Clear a specific namespace:

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
| SQLite | `CType::SQLite` |
| Redis | `CType::REDIS` |
| Memcached | `CType::MEMCACHED` |

## Quality

NCache is developed and tested using:

- PHPUnit
- PHPStan level 9
- PHP CS Fixer

Current unit test suite:

```text
298 tests
1060 assertions
```

NCache targets PHP 8.1 and newer.

## License

NCache is released under the MIT License.