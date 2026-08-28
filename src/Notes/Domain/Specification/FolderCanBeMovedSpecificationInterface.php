<?php

declare(strict_types=1);

namespace App\Notes\Domain\Specification;

use App\Notes\Domain\ValueObject\FolderId;

interface FolderCanBeMovedSpecificationInterface
{
    /**
     * @throws \App\Notes\Domain\Exception\FolderCycleDetected
     */
    public function assertCanBeMoved(FolderId $folderId, ?FolderId $targetParentFolderId): void;
}
