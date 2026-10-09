<?php

declare(strict_types=1);

namespace App\Notes\Domain\Service;

use App\Notes\Domain\Model\Note;
use App\Notes\Domain\Repository\AttachmentRepository;
use App\Notes\Domain\ValueObject\OwnerId;

/**
 * Attachments bound to a note live and die with it: they go to the trash, come back and are
 * permanently removed (rows and files) together with their note.
 */
class NoteAttachmentLifecycle
{
    public function __construct(
        private AttachmentRepository $attachmentRepository,
        private FileStoragePort $fileStorage
    ) {
    }

    public function trash(Note $note, OwnerId $ownerId, \DateTimeInterface $now): void
    {
        foreach ($this->attachmentRepository->findAllByNote($note->getId(), $ownerId) as $attachment) {
            $attachment->moveToTrash($now);
            $this->attachmentRepository->save($attachment);
        }
    }

    public function restore(Note $note, OwnerId $ownerId): void
    {
        foreach ($this->attachmentRepository->findAllByNote($note->getId(), $ownerId) as $attachment) {
            $attachment->restore();
            $this->attachmentRepository->save($attachment);
        }
    }

    public function deletePermanently(Note $note, OwnerId $ownerId): void
    {
        foreach ($this->attachmentRepository->findAllByNote($note->getId(), $ownerId) as $attachment) {
            $this->fileStorage->delete($attachment->getStorageKey()->value());
            $this->attachmentRepository->delete($attachment);
        }
    }
}
