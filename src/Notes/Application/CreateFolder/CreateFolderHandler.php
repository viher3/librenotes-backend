<?php

declare(strict_types=1);

namespace App\Notes\Application\CreateFolder;

use App\Core\Application\Command\TransactionalCommandHandler;
use App\Core\Domain\Time\DateTimeProvider;
use App\Notes\Domain\Exception\FolderNotFound;
use App\Notes\Domain\Model\Folder;
use App\Notes\Domain\Repository\FolderRepository;
use App\Notes\Domain\ValueObject\FolderId;
use App\Notes\Domain\ValueObject\FolderName;
use App\Notes\Domain\ValueObject\Icon;
use App\Notes\Domain\ValueObject\OwnerId;

class CreateFolderHandler implements TransactionalCommandHandler
{
    public function __construct(
        private FolderRepository $folderRepository,
        private DateTimeProvider $dateTimeProvider
    ) {
    }

    public function __invoke(CreateFolderCommand $command): void
    {
        $ownerId = new OwnerId($command->ownerId);
        $parentFolderId = null;

        if ($command->parentFolderId !== null) {
            $parentFolderId = new FolderId($command->parentFolderId);
            $parent = $this->folderRepository->getById($parentFolderId->value());

            if ($parent === null || !$parent->belongsTo($ownerId) || $parent->isDeleted()) {
                throw new FolderNotFound($command->parentFolderId);
            }
        }

        $folder = new Folder(
            id: new FolderId($command->id),
            name: new FolderName($command->name),
            parentFolderId: $parentFolderId,
            ownerId: $ownerId,
            createdAt: $this->dateTimeProvider->now(),
            icon: Icon::fromNullable($command->icon)
        );

        $this->folderRepository->save($folder);
    }
}
