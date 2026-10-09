<?php

declare(strict_types=1);

namespace App\Notes\Application\MoveLink;

use App\Core\Application\Command\TransactionalCommandHandler;
use App\Core\Domain\Time\DateTimeProvider;
use App\Notes\Domain\Exception\FolderNotFound;
use App\Notes\Domain\Exception\LinkNotFound;
use App\Notes\Domain\Repository\FolderRepository;
use App\Notes\Domain\Repository\LinkRepository;
use App\Notes\Domain\ValueObject\FolderId;
use App\Notes\Domain\ValueObject\OwnerId;

class MoveLinkHandler implements TransactionalCommandHandler
{
    public function __construct(
        private LinkRepository $linkRepository,
        private FolderRepository $folderRepository,
        private DateTimeProvider $dateTimeProvider
    ) {
    }

    public function __invoke(MoveLinkCommand $command): void
    {
        $ownerId = new OwnerId($command->ownerId);
        $link = $this->linkRepository->getById($command->id);

        if ($link === null || !$link->belongsTo($ownerId) || $link->isDeleted()) {
            throw new LinkNotFound($command->id);
        }

        $folderId = null;

        if ($command->folderId !== null) {
            $folderId = new FolderId($command->folderId);
            $folder = $this->folderRepository->getById($folderId->value());

            if ($folder === null || !$folder->belongsTo($ownerId) || $folder->isDeleted()) {
                throw new FolderNotFound($command->folderId);
            }
        }

        $link->moveTo($folderId, $this->dateTimeProvider->now());
        $this->linkRepository->save($link);
    }
}
