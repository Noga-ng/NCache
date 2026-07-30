<?php

declare(strict_types=1);

namespace NCache\Core\Files;

use JsonException;
use NCache\Enum\CType;
use NCache\Exceptions\FailedReadCacheException;
use Throwable;

final class ReadFile
{
    public function __construct(
        private readonly string $file,
        private readonly CType $type
    ) {
        if (!is_file($this->file)) {
            throw new FailedReadCacheException(
                "Le fichier '{$this->file}' n'existe pas."
            );
        }

        if (!is_readable($this->file)) {
            throw new FailedReadCacheException(
                "Le fichier '{$this->file}' n'est pas lisible."
            );
        }
    }

    /**
     * @return array<int|string, mixed>|string
     */
    public function get(): array|string
    {
        return match ($this->type) {
            CType::SERIALIZE => $this->loadSerializedArray(),
            CType::JSON => $this->decodeJson(),
            CType::STRING => $this->read(),
            default =>$this->read()
        };
    }

    /**
     * @return array<int|string,mixed>
     */
    private function decodeJson(): array
    {
        try {
            $data = json_decode(
                $this->read(),
                true,
                512,
                JSON_THROW_ON_ERROR
            );
        } catch (JsonException $exception) {
            throw new FailedReadCacheException(
                "Le fichier JSON '{$this->file}' est invalide.",
                previous: $exception
            );
        }

        if (!\is_array($data)) {
            throw new FailedReadCacheException(
                "Le fichier JSON '{$this->file}' doit contenir un tableau."
            );
        }

        return $data;
    }

    private function read(): string
    {
        $handle = fopen($this->file, 'rb');

        if ($handle === false) {
            throw new FailedReadCacheException(
                "Impossible d'ouvrir le fichier '{$this->file}'."
            );
        }

        $locked = false;

        try {
            $locked = flock($handle, LOCK_SH);

            if (!$locked) {
                throw new FailedReadCacheException(
                    "Impossible de verrouiller le fichier '{$this->file}'."
                );
            }

            $content = '';

            while (!feof($handle)) {
                $chunk = fread($handle, 8192);

                if ($chunk === false) {
                    throw new FailedReadCacheException(
                        "Erreur pendant la lecture de '{$this->file}'."
                    );
                }

                $content .= $chunk;
            }

            return $content;
        } finally {
            if ($locked) {
                flock($handle, LOCK_UN);
            }

            fclose($handle);
        }
    }

/**
 * @throws FailedReadCacheException
 * @return array<int|string,mixed>
 */
private function loadSerializedArray(): array
{
    $content = $this->read();

    try {
        $data = @unserialize(
    $content,
    ['allowed_classes' => false]
);

if ($data === false && $content !== serialize(false)) {
    throw new FailedReadCacheException(
        sprintf('Invalid serialized cache data in file: %s', $this->file)
    );
}

    } catch (Throwable $exception) {
        throw new FailedReadCacheException(
            "Le fichier de cache '{$this->file}' est corrompu.",
            previous: $exception
        );
    }

    if (!\is_array($data)) {
        throw new FailedReadCacheException(
            "Le fichier '{$this->file}' doit contenir un tableau sérialisé."
        );
    }

    return $data;
}

}