<?php

declare(strict_types=1);

namespace App\Notes\Application\MoveNoteToTrash;

use App\Core\Application\Command\CommandHandler;
use App\Core\Domain\Time\DateTimeProvider;
use App\Notes\Domain\Exception\NoteNotFound;
use App\Notes\Domain\Repository\NoteRepository;
use App\Notes\Domain\ValueObject\OwnerId;

class MoveNoteToTrashHandler implements CommandHandler
{
    public function __construct(
        private NoteRepository $noteRepository,
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

        $note->moveToTrash($this->dateTimeProvider->now());

        $this->noteRepository->save($note);
    }
}
