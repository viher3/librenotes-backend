<?php

declare(strict_types=1);

namespace App\Notes\Application\DeleteNote;

use App\Core\Application\Command\CommandHandler;
use App\Notes\Domain\Exception\NoteNotFound;
use App\Notes\Domain\Repository\NoteRepository;
use App\Notes\Domain\ValueObject\OwnerId;

class DeleteNoteHandler implements CommandHandler
{
    public function __construct(
        private NoteRepository $noteRepository
    ) {
    }

    public function __invoke(DeleteNoteCommand $command): void
    {
        $ownerId = new OwnerId($command->ownerId);
        $note = $this->noteRepository->getById($command->id);

        if ($note === null || !$note->belongsTo($ownerId)) {
            throw new NoteNotFound($command->id);
        }

        $this->noteRepository->delete($note);
    }
}
