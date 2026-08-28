<?php

declare(strict_types=1);

namespace App\Notes\Domain\Exception;

use App\Core\Domain\Exception\DomainError;

class FolderCycleDetected extends DomainError
{
    public function __construct(
        private string $folderId,
        private string $targetParentFolderId
    ) {
        parent::__construct(sprintf(
            'Cannot move folder %s into %s: it would create a cycle',
            $this->folderId,
            $this->targetParentFolderId
        ));
    }

    public function errorCode(): string
    {
        return 'folder.cycle_detected';
    }

    public function params(): array
    {
        return [
            'folderId' => $this->folderId,
            'targetParentFolderId' => $this->targetParentFolderId,
        ];
    }
}
