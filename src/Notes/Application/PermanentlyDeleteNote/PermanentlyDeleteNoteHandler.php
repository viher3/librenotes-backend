<?php

declare(strict_types=1);

namespace App\Notes\Application\PermanentlyDeleteNote;

use App\Core\Application\Command\CommandHandler;
use App\Notes\Domain\Exception\NoteNotFound;
use App\Notes\Domain\Exception\NotInTrash;
use App\Notes\Domain\Repository\NoteRepository;
use App\Notes\Domain\ValueObject\OwnerId;

class PermanentlyDeleteNoteHandler implements CommandHandler
{
    public function __construct(
        private NoteRepository $noteRepository
    ) {
    }

    public function __invoke(PermanentlyDeleteNoteCommand $command): void
    {
        $ownerId = new OwnerId($command->ownerId);
        $note = $this->noteRepository->getById($command->id);

        if ($note === null || !$note->belongsTo($ownerId)) {
            throw new NoteNotFound($command->id);
        }

        if (!$note->isDeleted()) {
            throw new NotInTrash($command->id);
        }

        $this->noteRepository->delete($note);
    }
}
