<?php

declare(strict_types=1);

namespace App\Notes\Application\RestoreNote;

use App\Core\Application\Command\TransactionalCommandHandler;
use App\Notes\Domain\Exception\NoteNotFound;
use App\Notes\Domain\Exception\NotInTrash;
use App\Notes\Domain\Exception\ParentFolderInTrash;
use App\Notes\Domain\Exception\ParentNoteInTrash;
use App\Notes\Domain\Repository\FolderRepository;
use App\Notes\Domain\Repository\NoteRepository;
use App\Notes\Domain\Service\NoteTreeLifecycle;
use App\Notes\Domain\ValueObject\OwnerId;

class RestoreNoteHandler implements TransactionalCommandHandler
{
    public function __construct(
        private NoteRepository $noteRepository,
        private FolderRepository $folderRepository,
        private NoteTreeLifecycle $noteTree
    ) {
    }

    public function __invoke(RestoreNoteCommand $command): void
    {
        $ownerId = new OwnerId($command->ownerId);
        $note = $this->noteRepository->getById($command->id);

        if ($note === null || !$note->belongsTo($ownerId)) {
            throw new NoteNotFound($command->id);
        }

        if (!$note->isDeleted()) {
            throw new NotInTrash($command->id);
        }

        if ($note->getFolderId() !== null) {
            $folder = $this->folderRepository->getById($note->getFolderId()->value());

            if ($folder !== null && $folder->isDeleted()) {
                throw new ParentFolderInTrash($folder->getId()->value());
            }
        }

        if ($note->getParentNoteId() !== null) {
            $parent = $this->noteRepository->getById($note->getParentNoteId()->value());

            if ($parent !== null && $parent->isDeleted()) {
                throw new ParentNoteInTrash($parent->getId()->value());
            }
        }

        $note->restore();

        $this->noteRepository->save($note);
        $this->noteTree->restore($note, $ownerId);
    }
}
