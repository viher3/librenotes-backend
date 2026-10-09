<?php

declare(strict_types=1);

namespace App\Notes\Domain\Service;

use App\Notes\Domain\Model\Note;
use App\Notes\Domain\Repository\LinkRepository;
use App\Notes\Domain\Repository\NoteRepository;
use App\Notes\Domain\ValueObject\OwnerId;

/**
 * What a note carries with it: its attachments, and everything under it (notes, links, and their own
 * attachments). Sending a note to the trash, restoring it or deleting it for good does the same to all of it.
 * The note itself is handled by the caller.
 */
class NoteTreeLifecycle
{
    /** Guards against corrupt data: a legitimate tree is never this deep. */
    private const int MAX_DEPTH = 100;

    public function __construct(
        private NoteRepository $noteRepository,
        private LinkRepository $linkRepository,
        private NoteAttachmentLifecycle $attachments
    ) {
    }

    public function trash(Note $note, OwnerId $ownerId, \DateTimeInterface $now, int $depth = 0): void
    {
        $this->attachments->trash($note, $ownerId, $now);
        if ($depth >= self::MAX_DEPTH) {
            return;
        }

        foreach ($this->noteRepository->findAllByParent($note->getId(), $ownerId) as $child) {
            $child->moveToTrash($now);
            $this->noteRepository->save($child);
            $this->trash($child, $ownerId, $now, $depth + 1);
        }
        foreach ($this->linkRepository->findAllByParent($note->getId(), $ownerId) as $link) {
            $link->moveToTrash($now);
            $this->linkRepository->save($link);
        }
    }

    public function restore(Note $note, OwnerId $ownerId, int $depth = 0): void
    {
        $this->attachments->restore($note, $ownerId);
        if ($depth >= self::MAX_DEPTH) {
            return;
        }

        foreach ($this->noteRepository->findAllByParent($note->getId(), $ownerId) as $child) {
            $child->restore();
            $this->noteRepository->save($child);
            $this->restore($child, $ownerId, $depth + 1);
        }
        foreach ($this->linkRepository->findAllByParent($note->getId(), $ownerId) as $link) {
            $link->restore();
            $this->linkRepository->save($link);
        }
    }

    public function deletePermanently(Note $note, OwnerId $ownerId, int $depth = 0): void
    {
        if ($depth < self::MAX_DEPTH) {
            foreach ($this->noteRepository->findAllByParent($note->getId(), $ownerId) as $child) {
                $this->deletePermanently($child, $ownerId, $depth + 1);
                $this->noteRepository->delete($child);
            }
            foreach ($this->linkRepository->findAllByParent($note->getId(), $ownerId) as $link) {
                $this->linkRepository->delete($link);
            }
        }
        $this->attachments->deletePermanently($note, $ownerId);
    }
}
