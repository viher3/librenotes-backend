<?php

declare(strict_types=1);

namespace App\Notes\Application\RestoreFolder;

use App\Core\Application\Command\TransactionalCommandHandler;
use App\Notes\Domain\Exception\FolderNotFound;
use App\Notes\Domain\Exception\NotInTrash;
use App\Notes\Domain\Model\Folder;
use App\Notes\Domain\Repository\AttachmentRepository;
use App\Notes\Domain\Service\NoteTreeLifecycle;
use App\Notes\Domain\Repository\FolderRepository;
use App\Notes\Domain\Repository\LinkRepository;
use App\Notes\Domain\Repository\NoteRepository;
use App\Notes\Domain\ValueObject\OwnerId;

/**
 * Restores a folder together with everything that was trashed along with it
 * (subfolders, notes, attachments).
 */
class RestoreFolderHandler implements TransactionalCommandHandler
{
    public function __construct(
        private FolderRepository $folderRepository,
        private NoteRepository $noteRepository,
        private LinkRepository $linkRepository,
        private AttachmentRepository $attachmentRepository,
        private NoteTreeLifecycle $noteTree
    ) {
    }

    public function __invoke(RestoreFolderCommand $command): void
    {
        $ownerId = new OwnerId($command->ownerId);
        $folder = $this->folderRepository->getById($command->id);

        if ($folder === null || !$folder->belongsTo($ownerId)) {
            throw new FolderNotFound($command->id);
        }

        if (!$folder->isDeleted()) {
            throw new NotInTrash($command->id);
        }

        $this->restoreRecursively($folder, $ownerId);
    }

    private function restoreRecursively(Folder $folder, OwnerId $ownerId): void
    {
        $folder->restore();
        $this->folderRepository->save($folder);

        foreach ($this->noteRepository->findAllByFolder($folder->getId(), $ownerId) as $note) {
            $note->restore();
            $this->noteRepository->save($note);
            $this->noteTree->restore($note, $ownerId);
        }

        foreach ($this->linkRepository->findAllByFolder($folder->getId(), $ownerId) as $link) {
            $link->restore();
            $this->linkRepository->save($link);
        }

        foreach ($this->attachmentRepository->findAllByFolder($folder->getId(), $ownerId) as $attachment) {
            $attachment->restore();
            $this->attachmentRepository->save($attachment);
        }

        foreach ($this->folderRepository->findAllChildren($folder->getId(), $ownerId) as $childFolder) {
            $this->restoreRecursively($childFolder, $ownerId);
        }
    }
}
