<?php

declare(strict_types=1);

namespace App\Notes\Application\EmptyTrash;

use App\Core\Application\Command\TransactionalCommandHandler;
use App\Notes\Domain\Model\Folder;
use App\Notes\Domain\Repository\AttachmentRepository;
use App\Notes\Domain\Repository\FolderRepository;
use App\Notes\Domain\Repository\NoteRepository;
use App\Notes\Domain\Service\FileStoragePort;
use App\Notes\Domain\ValueObject\OwnerId;

/**
 * Permanently deletes everything currently in the trash for an owner: trashed
 * folders (with their whole subtree, which was trashed along with them) plus any
 * note/attachment trashed independently of a folder — including files on disk.
 */
class EmptyTrashHandler implements TransactionalCommandHandler
{
    public function __construct(
        private FolderRepository $folderRepository,
        private NoteRepository $noteRepository,
        private AttachmentRepository $attachmentRepository,
        private FileStoragePort $fileStorage
    ) {
    }

    public function __invoke(EmptyTrashCommand $command): void
    {
        $ownerId = new OwnerId($command->ownerId);

        foreach ($this->folderRepository->findTrashed($ownerId) as $folder) {
            if ($this->folderRepository->getById($folder->getId()->value()) === null) {
                // Already removed earlier in this loop, as part of an ancestor's cascade.
                continue;
            }

            $this->deleteFolderRecursively($folder, $ownerId);
        }

        foreach ($this->noteRepository->findTrashed($ownerId) as $note) {
            $this->noteRepository->delete($note);
        }

        foreach ($this->attachmentRepository->findTrashed($ownerId) as $attachment) {
            $this->fileStorage->delete($attachment->getStorageKey()->value());
            $this->attachmentRepository->delete($attachment);
        }
    }

    private function deleteFolderRecursively(Folder $folder, OwnerId $ownerId): void
    {
        foreach ($this->folderRepository->findAllChildren($folder->getId(), $ownerId) as $childFolder) {
            $this->deleteFolderRecursively($childFolder, $ownerId);
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
