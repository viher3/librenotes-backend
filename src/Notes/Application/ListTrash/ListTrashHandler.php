<?php

declare(strict_types=1);

namespace App\Notes\Application\ListTrash;

use App\Core\Application\Query\QueryHandler;
use App\Notes\Domain\Model\Attachment;
use App\Notes\Domain\Repository\AttachmentRepository;
use App\Notes\Domain\Repository\FolderRepository;
use App\Notes\Domain\Repository\LinkRepository;
use App\Notes\Domain\Repository\NoteRepository;
use App\Notes\Domain\ValueObject\OwnerId;

class ListTrashHandler implements QueryHandler
{
    public function __construct(
        private FolderRepository $folderRepository,
        private NoteRepository $noteRepository,
        private LinkRepository $linkRepository,
        private AttachmentRepository $attachmentRepository
    ) {
    }

    public function __invoke(ListTrashQuery $query): ListTrashResponse
    {
        $ownerId = new OwnerId($query->ownerId);

        return new ListTrashResponse(
            folders: $this->folderRepository->findTrashed($ownerId),
            notes: $this->noteRepository->findTrashed($ownerId),
            links: $this->linkRepository->findTrashed($ownerId),
            attachments: $this->trashedAttachmentsShownOnTheirOwn($ownerId)
        );
    }

    /**
     * Attachments bound to a trashed note travel with it and are not listed separately; only the ones
     * trashed on their own (or belonging to a folder) are.
     *
     * @return Attachment[]
     */
    private function trashedAttachmentsShownOnTheirOwn(OwnerId $ownerId): array
    {
        return array_values(array_filter(
            $this->attachmentRepository->findTrashed($ownerId),
            function (Attachment $attachment): bool {
                $noteId = $attachment->getNoteId();

                return $noteId === null || $this->noteRepository->getById($noteId->value())?->isDeleted() !== true;
            }
        ));
    }
}
