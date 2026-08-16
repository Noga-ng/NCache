<?php

declare(strict_types=1);

namespace NCache\Enum;

enum CType
{
    case SERIALIZE;
    case ARRAY_PHP;
    case JSON;
    case STRING;
    case REDIS;
    case SQLite;
    case MEMCACHED;
}
