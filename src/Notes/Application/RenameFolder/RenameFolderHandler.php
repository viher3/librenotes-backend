<?php

declare(strict_types=1);

namespace App\Notes\Application\RenameFolder;

use App\Core\Application\Command\TransactionalCommandHandler;
use App\Core\Domain\Time\DateTimeProvider;
use App\Notes\Domain\Exception\FolderNotFound;
use App\Notes\Domain\Repository\FolderRepository;
use App\Notes\Domain\ValueObject\FolderName;
use App\Notes\Domain\ValueObject\OwnerId;

class RenameFolderHandler implements TransactionalCommandHandler
{
    public function __construct(
        private FolderRepository $folderRepository,
        private DateTimeProvider $dateTimeProvider
    ) {
    }

    public function __invoke(RenameFolderCommand $command): void
    {
        $ownerId = new OwnerId($command->ownerId);
        $folder = $this->folderRepository->getById($command->id);

        if ($folder === null || !$folder->belongsTo($ownerId)) {
            throw new FolderNotFound($command->id);
        }

        $folder->rename(new FolderName($command->name), $this->dateTimeProvider->now());

        $this->folderRepository->save($folder);
    }
}
