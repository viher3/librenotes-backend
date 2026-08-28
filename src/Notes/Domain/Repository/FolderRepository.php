<?php

declare(strict_types=1);

namespace App\Notes\Domain\Repository;

use App\Notes\Domain\Model\Folder;
use App\Notes\Domain\ValueObject\FolderId;
use App\Notes\Domain\ValueObject\OwnerId;

interface FolderRepository
{
    public function save(Folder $folder): void;

    public function getById(string $id): ?Folder;

    /**
     * @return Folder[]
     */
    public function findChildren(?FolderId $parentFolderId, OwnerId $ownerId): array;

    public function isDescendantOf(FolderId $candidate, FolderId $ancestor): bool;

    public function delete(Folder $folder): void;
}
