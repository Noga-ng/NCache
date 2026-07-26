<?php

declare(strict_types=1);

namespace NCache\Core\Files;

use JsonException;
use NCache\Enum\CType;
use NCache\Exceptions\FailedReadFileException;
use Throwable;

final class ReadFile
{
    public function __construct(
        private readonly string $file,
        private readonly CType $type
    ) {
        if (!is_file($this->file)) {
            throw new FailedReadFileException(
                "Le fichier '{$this->file}' n'existe pas."
            );
        }

        if (!is_readable($this->file)) {
            throw new FailedReadFileException(
                "Le fichier '{$this->file}' n'est pas lisible."
            );
        }
    }

    /**
     * @return array<string, mixed>|string
     */
    public function get(): array|string
    {
        return match ($this->type) {
            CType::ARRAY => $this->loadSerializedArray(),
            CType::JSON => $this->decodeJson(),
            CType::STRING => $this->read(),
        };
    }

    /**
     * @return array<string, mixed>
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
            throw new FailedReadFileException(
                "Le fichier JSON '{$this->file}' est invalide.",
                previous: $exception
            );
        }

        if (!\is_array($data)) {
            throw new FailedReadFileException(
                "Le fichier JSON '{$this->file}' doit contenir un tableau."
            );
        }

        return $data;
    }

    private function read(): string
    {
        $handle = fopen($this->file, 'rb');

        if ($handle === false) {
            throw new FailedReadFileException(
                "Impossible d'ouvrir le fichier '{$this->file}'."
            );
        }

        $locked = false;

        try {
            $locked = flock($handle, LOCK_SH);

            if (!$locked) {
                throw new FailedReadFileException(
                    "Impossible de verrouiller le fichier '{$this->file}'."
                );
            }

            $content = '';

            while (!feof($handle)) {
                $chunk = fread($handle, 8192);

                if ($chunk === false) {
                    throw new FailedReadFileException(
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
     * @return array<string, mixed>
     */
    /**
 * @return array<string, mixed>
 */
private function loadSerializedArray(): array
{
    $content = $this->read();

    try {
        $data = unserialize(
            $content,
            ['allowed_classes' => false]
        );
    } catch (Throwable $exception) {
        throw new FailedReadFileException(
            "Le fichier de cache '{$this->file}' est corrompu.",
            previous: $exception
        );
    }

    if (!\is_array($data)) {
        throw new FailedReadFileException(
            "Le fichier '{$this->file}' doit contenir un tableau sérialisé."
        );
    }

    return $data;
}

}