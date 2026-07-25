<?php
declare(strict_types=1);

namespace NCache\Core;

final class Debug{
    /**
     * @param string|int|array<mixed> $content
     */
    public function __construct( 
        private readonly mixed $content,
        private readonly ?string $key = null,
        private readonly ?string $file = null,
    ){}

    /**
     * @return array{content: array<mixed>|int|string|null, file: string|null, key: string|null}
     */
    public function toArray():array{
        return [
            'key'=>$this->key,
            'file'=>$this->file,
            'content'=>$this->content
        ];
    }
}