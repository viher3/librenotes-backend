<?php

declare(strict_types=1);

namespace App\Notes\Application\RestoreLink;

use App\Core\Application\Command\TransactionalCommandHandler;
use App\Notes\Domain\Exception\LinkNotFound;
use App\Notes\Domain\Exception\NotInTrash;
use App\Notes\Domain\Exception\ParentFolderInTrash;
use App\Notes\Domain\Exception\ParentNoteInTrash;
use App\Notes\Domain\Repository\FolderRepository;
use App\Notes\Domain\Repository\LinkRepository;
use App\Notes\Domain\Repository\NoteRepository;
use App\Notes\Domain\ValueObject\OwnerId;

class RestoreLinkHandler implements TransactionalCommandHandler
{
    public function __construct(
        private LinkRepository $linkRepository,
        private FolderRepository $folderRepository,
        private NoteRepository $noteRepository
    ) {
    }

    public function __invoke(RestoreLinkCommand $command): void
    {
        $ownerId = new OwnerId($command->ownerId);
        $link = $this->linkRepository->getById($command->id);

        if ($link === null || !$link->belongsTo($ownerId)) {
            throw new LinkNotFound($command->id);
        }

        if (!$link->isDeleted()) {
            throw new NotInTrash($command->id);
        }

        if ($link->getFolderId() !== null) {
            $folder = $this->folderRepository->getById($link->getFolderId()->value());

            if ($folder !== null && $folder->isDeleted()) {
                throw new ParentFolderInTrash($folder->getId()->value());
            }
        }

        if ($link->getParentNoteId() !== null) {
            $parent = $this->noteRepository->getById($link->getParentNoteId()->value());

            if ($parent !== null && $parent->isDeleted()) {
                throw new ParentNoteInTrash($parent->getId()->value());
            }
        }

        $link->restore();
        $this->linkRepository->save($link);
    }
}
