<?php
declare(strict_types=1);

namespace NCache\Core;

use NCache\Exceptions\FailedCreationDirException;

final class CachePath
{
    public function __construct(
        private readonly string $path,
        private readonly int $permission = 0755
    ) {}

    /**
     * Retourne une nouvelle instance avec un sous-dossier ajouté.
     */
    public function dir(string $dir): static
    {
        $dir = trim($dir, "/\\");

        if ($dir === '') {
            return new static(
                $this->path,
                $this->permission
            );
        }

        return new static(
            rtrim($this->path, "/\\")
            . DIRECTORY_SEPARATOR
            . $dir,
            $this->permission
        );
    }

    /**
     * Retourne le chemin et crée le dossier s'il n'existe pas.
     *
     * @throws FailedCreationDirException
     */
    public function getPath(): string
    {
        if (
            !is_dir($this->path)
            && !mkdir($this->path, $this->permission, true)
            && !is_dir($this->path)
        ) {
            throw new FailedCreationDirException(
                "Failed to create cache directory: {$this->path}"
            );
        }

        return $this->path;
    }

    /**
     * Retourne le chemin sans créer le dossier.
     */
    public function value(): string
    {
        return $this->path;
    }

    /**
     * Vérifie si le dossier existe.
     */
    public function exists(): bool
    {
        return is_dir($this->path);
    }

    public function __toString(): string
    {
        return $this->path;
    }
}