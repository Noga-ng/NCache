<?php

declare(strict_types=1);

namespace NCache\Psr\SimpleCache\Exceptions;

final class InvalidCacheArgumentException extends \InvalidArgumentException implements \Psr\SimpleCache\InvalidArgumentException
{
}
