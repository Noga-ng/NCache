<?php
namespace NCache\Exceptions;

use Throwable;

final class CacheHandleException extends CacheException{
    public static function handle(Throwable $e):void{
       \print_r($e);
    }

}