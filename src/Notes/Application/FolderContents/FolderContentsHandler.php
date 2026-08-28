<?php

declare(strict_types=1);

namespace App\Notes\Application\FolderContents;

use App\Notes\Domain\Exception\FolderNotFound;
use App\Notes\Domain\Repository\AttachmentRepository;
use App\Notes\Domain\Repository\FolderRepository;
use App\Notes\Domain\Repository\NoteRepository;
use App\Notes\Domain\ValueObject\FolderId;
use App\Notes\Domain\ValueObject\OwnerId;
use App\Core\Application\Query\QueryHandler;

class FolderContentsHandler implements QueryHandler
{
    public function __construct(
        private FolderRepository $folderRepository,
        private NoteRepository $noteRepository,
        private AttachmentRepository $attachmentRepository
    ) {
    }

    public function __invoke(FolderContentsQuery $query): FolderContentsResponse
    {
        $ownerId = new OwnerId($query->ownerId);
        $folder = null;
        $folderId = null;

        if ($query->folderId !== null) {
            $folderId = new FolderId($query->folderId);
            $folder = $this->folderRepository->getById($folderId->value());

            if ($folder === null || !$folder->belongsTo($ownerId)) {
                throw new FolderNotFound($query->folderId);
            }
        }

        return new FolderContentsResponse(
            folder: $folder,
            subfolders: $this->folderRepository->findChildren($folderId, $ownerId),
            notes: $this->noteRepository->findByFolder($folderId, $ownerId),
            attachments: $folderId !== null ? $this->attachmentRepository->findByFolder($folderId, $ownerId) : []
        );
    }
}
