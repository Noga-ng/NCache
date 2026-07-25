<?php
namespace NCache\Exceptions;

use Throwable;

final class CacheHandleException extends CacheException{
    public static function handle(Throwable $e):Throwable{
        return $e;
    }

}