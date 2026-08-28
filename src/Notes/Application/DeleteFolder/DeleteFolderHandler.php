<?php

declare(strict_types=1);

namespace App\Notes\Application\DeleteFolder;

use App\Core\Application\Command\TransactionalCommandHandler;
use App\Notes\Domain\Exception\FolderNotFound;
use App\Notes\Domain\Model\Folder;
use App\Notes\Domain\Repository\AttachmentRepository;
use App\Notes\Domain\Repository\FolderRepository;
use App\Notes\Domain\Repository\NoteRepository;
use App\Notes\Domain\Service\FileStoragePort;
use App\Notes\Domain\ValueObject\OwnerId;

/**
 * Deletes a folder together with everything it contains: subfolders (recursively),
 * notes and attachments — including the attachments' underlying files on disk.
 */
class DeleteFolderHandler implements TransactionalCommandHandler
{
    public function __construct(
        private FolderRepository $folderRepository,
        private NoteRepository $noteRepository,
        private AttachmentRepository $attachmentRepository,
        private FileStoragePort $fileStorage
    ) {
    }

    public function __invoke(DeleteFolderCommand $command): void
    {
        $ownerId = new OwnerId($command->ownerId);
        $folder = $this->folderRepository->getById($command->id);

        if ($folder === null || !$folder->belongsTo($ownerId)) {
            throw new FolderNotFound($command->id);
        }

        $this->deleteRecursively($folder, $ownerId);
    }

    private function deleteRecursively(Folder $folder, OwnerId $ownerId): void
    {
        foreach ($this->folderRepository->findChildren($folder->getId(), $ownerId) as $childFolder) {
            $this->deleteRecursively($childFolder, $ownerId);
        }

        foreach ($this->noteRepository->findByFolder($folder->getId(), $ownerId) as $note) {
            $this->noteRepository->delete($note);
        }

        foreach ($this->attachmentRepository->findByFolder($folder->getId(), $ownerId) as $attachment) {
            $this->fileStorage->delete($attachment->getStorageKey()->value());
            $this->attachmentRepository->delete($attachment);
        }

        $this->folderRepository->delete($folder);
    }
}
