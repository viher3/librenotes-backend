<?php

declare(strict_types=1);

namespace App\Notes\Application\CreateNote;

use App\Core\Application\Command\TransactionalCommandHandler;
use App\Core\Domain\Time\DateTimeProvider;
use App\Notes\Domain\Model\Note;
use App\Notes\Domain\Repository\NoteRepository;
use App\Notes\Domain\Service\ContentPlacement;
use App\Notes\Domain\ValueObject\Icon;
use App\Notes\Domain\ValueObject\NoteContent;
use App\Notes\Domain\ValueObject\NoteId;
use App\Notes\Domain\ValueObject\NoteTitle;
use App\Notes\Domain\ValueObject\OwnerId;
use App\Notes\Domain\ValueObject\Tags;

class CreateNoteHandler implements TransactionalCommandHandler
{
    public function __construct(
        private NoteRepository $noteRepository,
        private ContentPlacement $placement,
        private DateTimeProvider $dateTimeProvider
    ) {
    }

    public function __invoke(CreateNoteCommand $command): void
    {
        $ownerId = new OwnerId($command->ownerId);
        [$folderId, $parentNoteId] = $this->placement->resolve($command->folderId, $command->parentNoteId, $ownerId);

        $note = new Note(
            id: new NoteId($command->id),
            title: new NoteTitle($command->title),
            content: new NoteContent($command->content),
            folderId: $folderId,
            ownerId: $ownerId,
            createdAt: $this->dateTimeProvider->now(),
            pinned: $command->pinned,
            tags: new Tags($command->tags),
            parentNoteId: $parentNoteId,
            icon: Icon::fromNullable($command->icon)
        );

        $this->noteRepository->save($note);
    }
}
