<?php

declare(strict_types=1);

namespace App\Notes\Domain\Service;

interface FileStoragePort
{
    public function store(string $sourceTmpPath, string $storageKey): void;

    public function delete(string $storageKey): void;
}
