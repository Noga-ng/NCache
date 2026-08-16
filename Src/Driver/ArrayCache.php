<?php

declare(strict_types=1);

namespace NCache\Driver;

use NCache\Core\CacheItem\CacheItem;
use NCache\Core\Files\CacheCleaner;
use NCache\Core\Files\ReadFile;
use NCache\Core\Files\WriteFile;
use NCache\Enum\CType;
use NCache\Exceptions\FailedReadCacheException;

final class ArrayCache extends CacheDriver
{
    private string $extension = 'php';

    public function __construct(CacheItem $item)
    {
        parent::__construct($item);

        $this->cacheCleaner = new CacheCleaner(
            [
                $this->extension,
            ],
        );
    }

    protected function format(): string
    {
        return '<?php'
            . PHP_EOL
            . PHP_EOL
            . 'return '
            . var_export(
                $this->item->getData(),
                true,
            )
            . ';'
            . PHP_EOL;
    }

    private function buildFile(): string
    {
        return $this->item->file()
            . ".{$this->extension}";
    }

    public function save(): bool
    {
        $file = $this->buildFile();

        $saved = (new WriteFile(
            $file,
            $this->format(),
        ))->save();

        if ($saved && function_exists('opcache_invalidate')) {
            @\opcache_invalidate(
                $file,
                true,
            );
        }

        return $saved;
    }

    /**
     * @return array<array-key, mixed>
     */
    public function get(): array
    {
        $data = (new ReadFile(
            $this->buildFile(),
            CType::ARRAY_PHP,
        ))->get();

        if (!\is_array($data)) {
            throw new FailedReadCacheException(
                'ArrayCache expected an array, got '
                . gettype($data)
                . ": '{$this->buildFile()}'.",
            );
        }

        return $data;
    }

    public function getFile(): string
    {
        return $this->buildFile();
    }

    public function exists(): bool
    {
        return is_file(
            $this->buildFile(),
        );
    }

    public function delete(): bool
    {
        return $this
            ->cacheCleaner
            ->delete(
                $this->buildFile(),
            );
    }

    public function clear(): int
    {
        return $this
            ->cacheCleaner
            ->clear(
                $this->item->path(),
            );
    }
}
