<?php

declare(strict_types=1);

namespace App\Notes\Application\MoveNote;

use App\Core\Application\Command\TransactionalCommandHandler;
use App\Core\Domain\Time\DateTimeProvider;
use App\Notes\Domain\Exception\NoteNotFound;
use App\Notes\Domain\Repository\NoteRepository;
use App\Notes\Domain\Service\ContentPlacement;
use App\Notes\Domain\Service\NoteHierarchy;
use App\Notes\Domain\ValueObject\OwnerId;

class MoveNoteHandler implements TransactionalCommandHandler
{
    public function __construct(
        private NoteRepository $noteRepository,
        private ContentPlacement $placement,
        private NoteHierarchy $hierarchy,
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

        [$folderId, $parentNoteId] = $this->placement->resolve($command->folderId, $command->parentNoteId, $ownerId);
        if ($parentNoteId !== null) {
            $this->hierarchy->assertCanBePlacedUnder($note, $parentNoteId);
        }

        $note->moveTo($folderId, $parentNoteId, $this->dateTimeProvider->now());

        $this->noteRepository->save($note);
    }
}
