<?php

declare(strict_types=1);

namespace App\Notes\Application\CreateNote;

use App\Core\Application\Command\TransactionalCommandHandler;
use App\Core\Domain\Time\DateTimeProvider;
use App\Notes\Domain\Exception\FolderNotFound;
use App\Notes\Domain\Model\Note;
use App\Notes\Domain\Repository\FolderRepository;
use App\Notes\Domain\Repository\NoteRepository;
use App\Notes\Domain\ValueObject\FolderId;
use App\Notes\Domain\ValueObject\NoteContent;
use App\Notes\Domain\ValueObject\NoteId;
use App\Notes\Domain\ValueObject\NoteTitle;
use App\Notes\Domain\ValueObject\OwnerId;

class CreateNoteHandler implements TransactionalCommandHandler
{
    public function __construct(
        private NoteRepository $noteRepository,
        private FolderRepository $folderRepository,
        private DateTimeProvider $dateTimeProvider
    ) {
    }

    public function __invoke(CreateNoteCommand $command): void
    {
        $ownerId = new OwnerId($command->ownerId);
        $folderId = null;

        if ($command->folderId !== null) {
            $folderId = new FolderId($command->folderId);
            $folder = $this->folderRepository->getById($folderId->value());

            if ($folder === null || !$folder->belongsTo($ownerId)) {
                throw new FolderNotFound($command->folderId);
            }
        }

        $note = new Note(
            id: new NoteId($command->id),
            title: new NoteTitle($command->title),
            content: new NoteContent($command->content),
            folderId: $folderId,
            ownerId: $ownerId,
            createdAt: $this->dateTimeProvider->now()
        );

        $this->noteRepository->save($note);
    }
}
