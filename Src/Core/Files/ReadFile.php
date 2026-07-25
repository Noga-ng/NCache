<?php
declare (strict_types = 1);

namespace NCache\Core\Files;

use NCache\Enum\CType;
use NCache\Exceptions\FailedReadFileException;

final class ReadFile
{
    public function __construct(
        private readonly string $file,
        private readonly CType $type
    ) {
        if (!is_file($this->file)) {
            throw new FailedReadFileException("Failed to read file {$this->file}");
        }
    }

    public function get(): mixed
    {
        return match ($this->type) {
            CType::ARRAY => $this->loadPhpFile(),
            CType::JSON => json_decode(
                $this->read(),
                true,
                512,
                JSON_THROW_ON_ERROR),
            CType::STRING => $this->read()
        };
    }

   private function read(): string
{
    $content = '';
    $fp = null;

    try {
        $fp = fopen($this->file, 'r');

        if ($fp === false) {
            throw new FailedReadFileException("Error fopen is failed");
        }

        while (($line = fgets($fp)) !== false) {
            $content .= $line;
        }

    } finally {
        if (\is_resource($fp)) {
            fclose($fp);
        }
    }

    return $content;
}

    private function loadPhpFile(): mixed
    {
        return require $this->file;
    }

}
