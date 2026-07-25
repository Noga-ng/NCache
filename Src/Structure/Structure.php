<?php
declare(strict_types=1);

namespace NCache\Structure;

use NCache\Config\Ttl\Expiration;
use NCache\Enum\CType;

final class Structure{

   public ?Expiration $expiration = null;
    /**
     * @param array<mixed>|bool|int|string $data
     */
    public function __construct(
        public string $key = "",
        public CType $type = CType::ARRAY,
        public ?string $basePath = null,
        public ?string $dir = null,
        public ?string $name = null,
        public string $signature = "",
        public ?int $ttl = null,
        public ?int $expiredAt = null,
        public mixed $data = []
    ){}

    public function ttl(?int $ttl): void
    {
        $this->expiration = Expiration::fromTTL($ttl);
    }

    public function ttlValue(): ?int
    {
        return $this->expiration?->ttl();
    }

    public function expiredAt(): ?int
    {
        return $this->expiration?->timestamp();
    }
    public function file():string{
        $file = ($this->dir !== null) ?
        $this->dir.DIRECTORY_SEPARATOR.$this->key :
        $this->name;
        return (string)$file;
    }

    /**
     * @return array<string,mixed>
     */
    public function toArray():array{
        return [
            'type'=>$this->type->name,
            'name'=>$this->name,
            'key'=>$this->key,
            'signature'=>$this->signature,
            'ttl'=>$this->ttlValue(),
            'expiredAt'=>$this->expiredAt(),
            'data'=>$this->data
        ];
    }
}
