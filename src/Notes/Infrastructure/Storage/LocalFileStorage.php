<?php

declare(strict_types=1);

namespace App\Notes\Infrastructure\Storage;

use App\Core\Domain\Exception\CriticalException;
use App\Notes\Domain\Service\FileStoragePort;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Stores attachment binaries on the local disk, outside the database — only
 * metadata (App\Notes\Domain\Model\Attachment) is persisted in BBDD.
 */
class LocalFileStorage implements FileStoragePort
{
    public function __construct(
        private string $storageDir,
        private Filesystem $filesystem
    ) {
    }

    public function store(string $sourceTmpPath, string $storageKey): void
    {
        try {
            $this->filesystem->mkdir($this->storageDir);
            $this->filesystem->copy($sourceTmpPath, $this->absolutePath($storageKey), true);
        } catch (IOExceptionInterface $exception) {
            throw new class ($exception->getMessage(), 0, $exception) extends CriticalException {
            };
        }
    }

    public function delete(string $storageKey): void
    {
        $this->filesystem->remove($this->absolutePath($storageKey));
    }

    public function read(string $storageKey)
    {
        $path = $this->absolutePath($storageKey);
        $stream = is_file($path) ? @fopen($path, 'rb') : false;

        if ($stream === false) {
            throw new class ('Stored file is missing or unreadable: ' . $storageKey) extends CriticalException {
            };
        }

        return $stream;
    }

    private function absolutePath(string $storageKey): string
    {
        return rtrim($this->storageDir, '/') . '/' . $storageKey;
    }
}
