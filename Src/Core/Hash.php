<?php
declare(strict_types=1);

namespace NCache\Core;

final class Hash{
    /**
     * @param string|int|array<mixed>|float $data
     * @param string $algo
     */
    public function __construct(
        private readonly mixed $data,
        private readonly string $algo = 'xxh128'
        ){}

    public function get():string{
        return match(true){
           \is_array($this->data) => hash(
            $this->algo,
            serialize($this->data)),
            default => hash($this->algo,(string)$this->data)
        };
    }
    
}