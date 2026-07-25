<?php
declare(strict_types=1);

namespace NCache\Core\Files;

use NCache\Exceptions\FailedWriteFileException;

final class PutData
{

    public function __construct(
        private readonly string $file,
        private readonly string $data,
        private readonly string $tmp
    ) {}

   public function save(): bool {

    $fp = fopen($this->tmp, "w");

    if ($fp === false) {
        throw new FailedWriteFileException("Cannot open tmp file");
    }

    if (!flock($fp, LOCK_EX)) {
        fclose($fp);
        throw new FailedWriteFileException("Cannot lock tmp file");
    }

     $written = fwrite($fp, $this->data);

    if ($written === false) {
        flock($fp, LOCK_UN);
        fclose($fp);

        throw new FailedWriteFileException("tmp write failed");
    }

    fflush($fp);

    flock($fp, LOCK_UN);

    fclose($fp);

    if (!rename($this->tmp, $this->file)) {
        throw new FailedWriteFileException("cache write failed :\n tmp => {$this->tmp}\n filename => {$this->file}");
    }
    
    return true;
}

}
