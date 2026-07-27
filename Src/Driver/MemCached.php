<?php
declare(strict_types=1);

namespace NCache\Driver;


final class MemCached extends CacheDriver{
     public function get(): mixed{
        return 0;
    }

    public function save(): bool{
        return true;
    }

    public function format(): mixed{
        return "";
    }

    public function exists(): bool{
        return true;
    }
}