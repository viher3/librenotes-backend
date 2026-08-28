<?php

declare(strict_types=1);

namespace App\Notes\Domain\Exception;

use App\Core\Domain\Exception\DomainError;

class ParentFolderInTrash extends DomainError
{
    public function __construct(
        private string $folderId
    ) {
        parent::__construct(sprintf(
            'Cannot restore: parent folder %s is still in trash. Restore it first.',
            $this->folderId
        ));
    }

    public function errorCode(): string
    {
        return 'notes.parent_folder_in_trash';
    }

    public function params(): array
    {
        return [
            'folderId' => $this->folderId,
        ];
    }
}
