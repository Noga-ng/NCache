<?php

declare(strict_types=1);

namespace NCache\Enum;

enum CType
{
    case SERIALIZE;
    case JSON;
    case STRING;
    case REDIS;
    case SQLite;
    case MEMCACHED;
}
