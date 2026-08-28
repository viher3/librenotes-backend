<?php

declare(strict_types=1);

namespace App\Notes\Application\MoveFolder;

use App\Core\Application\Command\TransactionalCommandHandler;
use App\Core\Domain\Time\DateTimeProvider;
use App\Notes\Domain\Exception\FolderNotFound;
use App\Notes\Domain\Repository\FolderRepository;
use App\Notes\Domain\Specification\FolderCanBeMovedSpecificationInterface;
use App\Notes\Domain\ValueObject\FolderId;
use App\Notes\Domain\ValueObject\OwnerId;

class MoveFolderHandler implements TransactionalCommandHandler
{
    public function __construct(
        private FolderRepository $folderRepository,
        private FolderCanBeMovedSpecificationInterface $folderCanBeMovedSpecification,
        private DateTimeProvider $dateTimeProvider
    ) {
    }

    public function __invoke(MoveFolderCommand $command): void
    {
        $ownerId = new OwnerId($command->ownerId);
        $folder = $this->folderRepository->getById($command->id);

        if ($folder === null || !$folder->belongsTo($ownerId)) {
            throw new FolderNotFound($command->id);
        }

        $targetParentFolderId = null;

        if ($command->parentFolderId !== null) {
            $targetParentFolderId = new FolderId($command->parentFolderId);
            $targetParent = $this->folderRepository->getById($targetParentFolderId->value());

            if ($targetParent === null || !$targetParent->belongsTo($ownerId)) {
                throw new FolderNotFound($command->parentFolderId);
            }
        }

        $this->folderCanBeMovedSpecification->assertCanBeMoved($folder->getId(), $targetParentFolderId);

        $folder->moveTo($targetParentFolderId, $this->dateTimeProvider->now());

        $this->folderRepository->save($folder);
    }
}
