<?php

declare(strict_types=1);

namespace NCache\Contract;

interface ConfigInterface
{
    public static function config(?string $filename = null): self;
    public function use(string $profile): static;
}
