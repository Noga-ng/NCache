<?php

declare(strict_types=1);

namespace NCache\Enum;

enum TtlState
{
    case PERSISTENT;
    case FRESH;
    case STALE;
    case EXPIRED;
}
