<?php

declare(strict_types=1);

namespace NCache\Contract;

interface SignatureInterface
{
    public function validate(): bool;
}
