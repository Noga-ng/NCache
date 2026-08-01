<?php
declare(strict_types=1);

namespace NCache\Core;

use NCache\Exceptions\FailedCreationDirException;

final class CachePath
{
    private string $path = '';
    public function __construct(
        private readonly string $basePath,
        private readonly int $permission = 0755
    ) {
        $this->path = $this->basePath;
      }

    /**
     * Retourne une nouvelle instance avec un sous-dossier ajouté.
     */
    public function dir(string $dir): CachePath
    {
        $clone = clone $this;
        $clone->path = empty($dir) ? rtrim($clone->basePath,'/\\') : 
            rtrim($clone->basePath, "/\\")
            . DIRECTORY_SEPARATOR
            . trim($dir,'/\\'); 

       return $clone;
    }

    /**
     * @throws FailedCreationDirException
     */
    public function getPath(): string
    {
        if (
            !is_dir($this->path)
            && !mkdir(
                $this->path, 
            $this->permission, 
            true
            )
            && 
            !is_dir($this->path)
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

    public function exists(): bool
    {
        return is_dir($this->path);
    }

    public function __toString(): string
    {
        return $this->path;
    }

    public function geBasePath():string{
        return rtrim(
            $this->basePath,
            '/\\'
        );
    }
}