<?php

declare(strict_types=1);

namespace App\Notes\Application\MoveNoteToTrash;

use App\Core\Application\Command\TransactionalCommandHandler;
use App\Core\Domain\Time\DateTimeProvider;
use App\Notes\Domain\Exception\NoteNotFound;
use App\Notes\Domain\Repository\NoteRepository;
use App\Notes\Domain\Service\NoteTreeLifecycle;
use App\Notes\Domain\ValueObject\OwnerId;

class MoveNoteToTrashHandler implements TransactionalCommandHandler
{
    public function __construct(
        private NoteRepository $noteRepository,
        private NoteTreeLifecycle $noteTree,
        private DateTimeProvider $dateTimeProvider
    ) {
    }

    public function __invoke(MoveNoteToTrashCommand $command): void
    {
        $ownerId = new OwnerId($command->ownerId);
        $note = $this->noteRepository->getById($command->id);

        if ($note === null || !$note->belongsTo($ownerId)) {
            throw new NoteNotFound($command->id);
        }

        $now = $this->dateTimeProvider->now();
        $note->moveToTrash($now);

        $this->noteRepository->save($note);
        $this->noteTree->trash($note, $ownerId, $now);
    }
}
