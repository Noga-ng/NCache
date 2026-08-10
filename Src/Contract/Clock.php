<?php

declare(strict_types=1);

namespace NCache\Contract;

interface Clock
{
    /**
     * @return int
     */
    public function now(): int;
}
