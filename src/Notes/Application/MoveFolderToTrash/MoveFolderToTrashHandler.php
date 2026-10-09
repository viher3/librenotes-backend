<?php

declare(strict_types=1);

namespace App\Notes\Application\MoveFolderToTrash;

use App\Core\Application\Command\TransactionalCommandHandler;
use App\Core\Domain\Time\DateTimeProvider;
use App\Notes\Domain\Exception\FolderNotFound;
use App\Notes\Domain\Model\Folder;
use App\Notes\Domain\Repository\AttachmentRepository;
use App\Notes\Domain\Service\NoteAttachmentLifecycle;
use App\Notes\Domain\Repository\FolderRepository;
use App\Notes\Domain\Repository\LinkRepository;
use App\Notes\Domain\Repository\NoteRepository;
use App\Notes\Domain\ValueObject\OwnerId;

/**
 * Moves a folder together with everything it contains (subfolders, notes, attachments)
 * to the trash. Files on disk are left untouched — only PermanentlyDelete removes them.
 */
class MoveFolderToTrashHandler implements TransactionalCommandHandler
{
    public function __construct(
        private FolderRepository $folderRepository,
        private NoteRepository $noteRepository,
        private LinkRepository $linkRepository,
        private AttachmentRepository $attachmentRepository,
        private NoteAttachmentLifecycle $noteAttachments,
        private DateTimeProvider $dateTimeProvider
    ) {
    }

    public function __invoke(MoveFolderToTrashCommand $command): void
    {
        $ownerId = new OwnerId($command->ownerId);
        $folder = $this->folderRepository->getById($command->id);

        if ($folder === null || !$folder->belongsTo($ownerId)) {
            throw new FolderNotFound($command->id);
        }

        $this->trashRecursively($folder, $ownerId, $this->dateTimeProvider->now());
    }

    private function trashRecursively(Folder $folder, OwnerId $ownerId, \DateTimeInterface $now): void
    {
        foreach ($this->folderRepository->findAllChildren($folder->getId(), $ownerId) as $childFolder) {
            $this->trashRecursively($childFolder, $ownerId, $now);
        }

        foreach ($this->noteRepository->findAllByFolder($folder->getId(), $ownerId) as $note) {
            $note->moveToTrash($now);
            $this->noteRepository->save($note);
            $this->noteAttachments->trash($note, $ownerId, $now);
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
