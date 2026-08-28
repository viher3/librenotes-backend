<?php

declare(strict_types=1);

namespace App\Notes\Application\ListTrash;

use App\Core\Application\Query\QueryHandler;
use App\Notes\Domain\Repository\AttachmentRepository;
use App\Notes\Domain\Repository\FolderRepository;
use App\Notes\Domain\Repository\NoteRepository;
use App\Notes\Domain\ValueObject\OwnerId;

class ListTrashHandler implements QueryHandler
{
    public function __construct(
        private FolderRepository $folderRepository,
        private NoteRepository $noteRepository,
        private AttachmentRepository $attachmentRepository
    ) {
    }

    public function __invoke(ListTrashQuery $query): ListTrashResponse
    {
        $ownerId = new OwnerId($query->ownerId);

        return new ListTrashResponse(
            folders: $this->folderRepository->findTrashed($ownerId),
            notes: $this->noteRepository->findTrashed($ownerId),
            attachments: $this->attachmentRepository->findTrashed($ownerId)
        );
    }
}
