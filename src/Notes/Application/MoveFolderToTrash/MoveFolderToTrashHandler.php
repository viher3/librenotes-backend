<?php

declare(strict_types=1);

namespace App\Notes\Application\MoveFolderToTrash;

use App\Core\Application\Command\TransactionalCommandHandler;
use App\Core\Domain\Time\DateTimeProvider;
use App\Notes\Domain\Exception\FolderNotFound;
use App\Notes\Domain\Repository\FolderRepository;
use App\Notes\Domain\Service\FolderTreeLifecycle;
use App\Notes\Domain\ValueObject\OwnerId;

/**
 * Moves a folder together with everything it contains (subfolders, notes, attachments)
 * to the trash. Files on disk are left untouched — only PermanentlyDelete removes them.
 */
class MoveFolderToTrashHandler implements TransactionalCommandHandler
{
    public function __construct(
        private FolderRepository $folderRepository,
        private FolderTreeLifecycle $folderTree,
        private DateTimeProvider $dateTimeProvider
    ) {
    }

    public function __invoke(MoveFolderToTrashCommand $command): void
    {
        $ownerId = new OwnerId($command->ownerId);
        $folder = $this->folderRepository->getById($command->id);

        if ($folder === null || !$folder->belongsTo($ownerId)) {
            throw new FolderNotFound($command->id);
        }

        $this->folderTree->trash($folder, $ownerId, $this->dateTimeProvider->now());
    }
}
