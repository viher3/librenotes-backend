<?php

declare(strict_types=1);

namespace App\Notes\Infrastructure\Specification;

use App\Notes\Domain\Exception\FolderCycleDetected;
use App\Notes\Domain\Repository\FolderRepository;
use App\Notes\Domain\Specification\FolderCanBeMovedSpecificationInterface;
use App\Notes\Domain\ValueObject\FolderId;

class FolderCanBeMovedSpecification implements FolderCanBeMovedSpecificationInterface
{
    public function __construct(
        private FolderRepository $folderRepository
    ) {
    }

    public function assertCanBeMoved(FolderId $folderId, ?FolderId $targetParentFolderId): void
    {
        if ($targetParentFolderId === null) {
            return;
        }

        if ($folderId->equals($targetParentFolderId) || $this->folderRepository->isDescendantOf($targetParentFolderId, $folderId)) {
            throw new FolderCycleDetected($folderId->value(), $targetParentFolderId->value());
        }
    }
}
