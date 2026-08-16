<?php

declare(strict_types=1);

namespace NCache\Psr\PsrCache\Exceptions;

use InvalidArgumentException;
use Psr\Cache\InvalidArgumentException as CacheInvalidArgumentException;

final class InvalidCacheArgumentException extends InvalidArgumentException implements CacheInvalidArgumentException
{
}
