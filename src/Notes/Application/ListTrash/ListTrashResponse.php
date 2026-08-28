<?php

declare(strict_types=1);

namespace App\Notes\Application\ListTrash;

use App\Core\Application\Query\QueryResponse;
use App\Notes\Domain\Model\Attachment;
use App\Notes\Domain\Model\Folder;
use App\Notes\Domain\Model\Note;
use App\Shared\Domain\Time\Date;

class ListTrashResponse implements QueryResponse
{
    /**
     * @param Folder[] $folders
     * @param Note[] $notes
     * @param Attachment[] $attachments
     */
    public function __construct(
        private array $folders,
        private array $notes,
        private array $attachments
    ) {
    }

    public function getResult(): array
    {
        return [
            'folders' => array_map(
                static fn (Folder $folder) => [
                    'id' => $folder->getId()->value(),
                    'name' => $folder->getName()->value(),
                    'parentFolderId' => $folder->getParentFolderId()?->value(),
                    'deletedAt' => $folder->getDeletedAt()?->value()->format(Date::DATE_DEFAULT_RENDER_FORMAT),
                ],
                $this->folders
            ),
            'notes' => array_map(
                static fn (Note $note) => [
                    'id' => $note->getId()->value(),
                    'title' => $note->getTitle()->value(),
                    'folderId' => $note->getFolderId()?->value(),
                    'deletedAt' => $note->getDeletedAt()?->value()->format(Date::DATE_DEFAULT_RENDER_FORMAT),
                ],
                $this->notes
            ),
            'attachments' => array_map(
                static fn (Attachment $attachment) => [
                    'id' => $attachment->getId()->value(),
                    'fileName' => $attachment->getFileName()->value(),
                    'folderId' => $attachment->getFolderId()->value(),
                    'deletedAt' => $attachment->getDeletedAt()?->value()->format(Date::DATE_DEFAULT_RENDER_FORMAT),
                ],
                $this->attachments
            ),
        ];
    }
}
