<?php

declare(strict_types=1);

namespace App\Notes\Application\RestoreAttachment;

use App\Core\Application\Command\TransactionalCommandHandler;
use App\Notes\Domain\Exception\AttachmentNotFound;
use App\Notes\Domain\Exception\NotInTrash;
use App\Notes\Domain\Exception\ParentFolderInTrash;
use App\Notes\Domain\Exception\ParentNoteInTrash;
use App\Notes\Domain\Repository\AttachmentRepository;
use App\Notes\Domain\Repository\FolderRepository;
use App\Notes\Domain\Repository\NoteRepository;
use App\Notes\Domain\ValueObject\OwnerId;

class RestoreAttachmentHandler implements TransactionalCommandHandler
{
    public function __construct(
        private AttachmentRepository $attachmentRepository,
        private FolderRepository $folderRepository,
        private NoteRepository $noteRepository
    ) {
    }

    public function __invoke(RestoreAttachmentCommand $command): void
    {
        $ownerId = new OwnerId($command->ownerId);
        $attachment = $this->attachmentRepository->getById($command->id);

        if ($attachment === null || !$attachment->belongsTo($ownerId)) {
            throw new AttachmentNotFound($command->id);
        }

        if (!$attachment->isDeleted()) {
            throw new NotInTrash($command->id);
        }

        if ($attachment->getNoteId() !== null) {
            $note = $this->noteRepository->getById($attachment->getNoteId()->value());

            if ($note !== null && $note->isDeleted()) {
                throw new ParentNoteInTrash($note->getId()->value());
            }
        }

        if ($attachment->getFolderId() !== null) {
            $folder = $this->folderRepository->getById($attachment->getFolderId()->value());

            if ($folder !== null && $folder->isDeleted()) {
                throw new ParentFolderInTrash($folder->getId()->value());
            }
        }

        $attachment->restore();

        $this->attachmentRepository->save($attachment);
    }
}
