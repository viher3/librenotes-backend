<?php

declare(strict_types=1);

namespace App\Notes\Application\PermanentlyDeleteNote;

use App\Core\Application\Command\TransactionalCommandHandler;
use App\Notes\Domain\Exception\NoteNotFound;
use App\Notes\Domain\Exception\NotInTrash;
use App\Notes\Domain\Repository\NoteRepository;
use App\Notes\Domain\Service\NoteTreeLifecycle;
use App\Notes\Domain\ValueObject\OwnerId;

class PermanentlyDeleteNoteHandler implements TransactionalCommandHandler
{
    public function __construct(
        private NoteRepository $noteRepository,
        private NoteTreeLifecycle $noteTree
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

        $this->noteTree->deletePermanently($note, $ownerId);
        $this->noteRepository->delete($note);
    }
}
