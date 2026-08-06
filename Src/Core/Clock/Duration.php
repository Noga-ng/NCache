<?php 
declare(strict_types=1);

namespace NCache\Core\Clock;

final class Duration
{
    public static function second(int $value):int
    {
        return $value;
    }
    
    public static function minutes(int $value): int
    {
        return $value * 60;
    }

    public static function hours(int $value): int
    {
        return $value * 3600;
    }

    public static function days(int $value): int
    {
        return $value * 86400;
    }

    public static function week(int $value): int
    {
        return $value * 604800;
    }

    public static function month(int $value): int
    {
        return $value * 2592000;
    }

    public static function make(int $days = 0, int $hours = 0, int $minutes = 0, int $second = 0): int
    {
        return self::days($days)
            + self::hours($hours)
            + self::minutes($minutes)
            + $second;
    }
}
