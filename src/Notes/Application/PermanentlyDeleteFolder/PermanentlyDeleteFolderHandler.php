<?php

declare(strict_types=1);

namespace App\Notes\Application\PermanentlyDeleteFolder;

use App\Core\Application\Command\TransactionalCommandHandler;
use App\Notes\Domain\Exception\FolderNotFound;
use App\Notes\Domain\Exception\NotInTrash;
use App\Notes\Domain\Model\Folder;
use App\Notes\Domain\Repository\AttachmentRepository;
use App\Notes\Domain\Repository\FolderRepository;
use App\Notes\Domain\Repository\NoteRepository;
use App\Notes\Domain\Service\FileStoragePort;
use App\Notes\Domain\ValueObject\OwnerId;

/**
 * Permanently deletes a folder that is already in the trash, together with
 * everything it contains — including the attachments' underlying files on disk.
 * Only reachable through the trash: the folder (and therefore its whole subtree,
 * trashed along with it) must already be soft-deleted.
 */
class PermanentlyDeleteFolderHandler implements TransactionalCommandHandler
{
    public function __construct(
        private FolderRepository $folderRepository,
        private NoteRepository $noteRepository,
        private AttachmentRepository $attachmentRepository,
        private FileStoragePort $fileStorage
    ) {
    }

    public function __invoke(PermanentlyDeleteFolderCommand $command): void
    {
        $ownerId = new OwnerId($command->ownerId);
        $folder = $this->folderRepository->getById($command->id);

        if ($folder === null || !$folder->belongsTo($ownerId)) {
            throw new FolderNotFound($command->id);
        }

        if (!$folder->isDeleted()) {
            throw new NotInTrash($command->id);
        }

        $this->deleteRecursively($folder, $ownerId);
    }

    private function deleteRecursively(Folder $folder, OwnerId $ownerId): void
    {
        foreach ($this->folderRepository->findAllChildren($folder->getId(), $ownerId) as $childFolder) {
            $this->deleteRecursively($childFolder, $ownerId);
        }

        foreach ($this->noteRepository->findAllByFolder($folder->getId(), $ownerId) as $note) {
            $this->noteRepository->delete($note);
        }

        foreach ($this->attachmentRepository->findAllByFolder($folder->getId(), $ownerId) as $attachment) {
            $this->fileStorage->delete($attachment->getStorageKey()->value());
            $this->attachmentRepository->delete($attachment);
        }

        $this->folderRepository->delete($folder);
    }
}
