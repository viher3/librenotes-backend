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
     * Direct subfolders that are not in trash — backs normal browsing.
     *
     * @return Folder[]
     */
    public function findChildren(?FolderId $parentFolderId, OwnerId $ownerId): array;

    /**
     * Direct subfolders regardless of trash state — backs trash/restore cascades.
     *
     * @return Folder[]
     */
    public function findAllChildren(?FolderId $parentFolderId, OwnerId $ownerId): array;

    /**
     * @return Folder[]
     */
    public function findTrashed(OwnerId $ownerId): array;

    public function isDescendantOf(FolderId $candidate, FolderId $ancestor): bool;

    public function delete(Folder $folder): void;
}
