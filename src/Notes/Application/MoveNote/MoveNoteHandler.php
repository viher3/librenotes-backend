<?php

declare(strict_types=1);

namespace App\Notes\Application\MoveNote;

use App\Core\Application\Command\TransactionalCommandHandler;
use App\Core\Domain\Time\DateTimeProvider;
use App\Notes\Domain\Exception\FolderNotFound;
use App\Notes\Domain\Exception\NoteNotFound;
use App\Notes\Domain\Repository\FolderRepository;
use App\Notes\Domain\Repository\NoteRepository;
use App\Notes\Domain\ValueObject\FolderId;
use App\Notes\Domain\ValueObject\OwnerId;

class MoveNoteHandler implements TransactionalCommandHandler
{
    public function __construct(
        private NoteRepository $noteRepository,
        private FolderRepository $folderRepository,
        private DateTimeProvider $dateTimeProvider
    ) {
    }

    public function __invoke(MoveNoteCommand $command): void
    {
        $ownerId = new OwnerId($command->ownerId);
        $note = $this->noteRepository->getById($command->id);

        if ($note === null || !$note->belongsTo($ownerId)) {
            throw new NoteNotFound($command->id);
        }

        $folderId = null;

        if ($command->folderId !== null) {
            $folderId = new FolderId($command->folderId);
            $folder = $this->folderRepository->getById($folderId->value());

            if ($folder === null || !$folder->belongsTo($ownerId) || $folder->isDeleted()) {
                throw new FolderNotFound($command->folderId);
            }
        }

        $note->moveTo($folderId, $this->dateTimeProvider->now());

        $this->noteRepository->save($note);
    }
}
