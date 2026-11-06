<?php

declare(strict_types=1);

namespace App\Notes\Domain\Service;

use App\Notes\Domain\Model\Folder;
use App\Notes\Domain\Repository\AttachmentRepository;
use App\Notes\Domain\Repository\FolderRepository;
use App\Notes\Domain\Repository\LinkRepository;
use App\Notes\Domain\Repository\NoteRepository;
use App\Notes\Domain\ValueObject\OwnerId;

/**
 * What a folder carries with it: subfolders, notes (with everything under them), links and attachments.
 * Sending a folder to the trash does the same to all of it. Files on disk are left untouched — only a
 * permanent delete removes them.
 */
class FolderTreeLifecycle
{
    public function __construct(
        private FolderRepository $folderRepository,
        private NoteRepository $noteRepository,
        private LinkRepository $linkRepository,
        private AttachmentRepository $attachmentRepository,
        private NoteTreeLifecycle $noteTree
    ) {
    }

    public function trash(Folder $folder, OwnerId $ownerId, \DateTimeInterface $now): void
    {
        foreach ($this->folderRepository->findAllChildren($folder->getId(), $ownerId) as $childFolder) {
            $this->trash($childFolder, $ownerId, $now);
        }

        foreach ($this->noteRepository->findAllByFolder($folder->getId(), $ownerId) as $note) {
            $note->moveToTrash($now);
            $this->noteRepository->save($note);
            $this->noteTree->trash($note, $ownerId, $now);
        }

        foreach ($this->linkRepository->findAllByFolder($folder->getId(), $ownerId) as $link) {
            $link->moveToTrash($now);
            $this->linkRepository->save($link);
        }

        foreach ($this->attachmentRepository->findAllByFolder($folder->getId(), $ownerId) as $attachment) {
            $attachment->moveToTrash($now);
            $this->attachmentRepository->save($attachment);
        }

        $folder->moveToTrash($now);
        $this->folderRepository->save($folder);
    }
}
