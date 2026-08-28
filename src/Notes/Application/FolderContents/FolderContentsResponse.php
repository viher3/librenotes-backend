<?php

declare(strict_types=1);

namespace App\Notes\Application\FolderContents;

use App\Core\Application\Query\QueryResponse;
use App\Notes\Domain\Model\Attachment;
use App\Notes\Domain\Model\Folder;
use App\Notes\Domain\Model\Note;
use App\Shared\Domain\Time\Date;

class FolderContentsResponse implements QueryResponse
{
    /**
     * @param Folder[] $subfolders
     * @param Note[] $notes
     * @param Attachment[] $attachments
     */
    public function __construct(
        private ?Folder $folder,
        private array $subfolders,
        private array $notes,
        private array $attachments
    ) {
    }

    public function getResult(): array
    {
        return [
            'folder' => $this->folder ? [
                'id' => $this->folder->getId()->value(),
                'name' => $this->folder->getName()->value(),
                'parentFolderId' => $this->folder->getParentFolderId()?->value(),
            ] : null,
            'subfolders' => array_map(
                static fn (Folder $folder) => [
                    'id' => $folder->getId()->value(),
                    'name' => $folder->getName()->value(),
                    'createdAt' => $folder->getCreatedAt()->value()->format(Date::DATE_DEFAULT_RENDER_FORMAT),
                ],
                $this->subfolders
            ),
            'notes' => array_map(
                static fn (Note $note) => [
                    'id' => $note->getId()->value(),
                    'title' => $note->getTitle()->value(),
                    'createdAt' => $note->getCreatedAt()->value()->format(Date::DATE_DEFAULT_RENDER_FORMAT),
                    'updatedAt' => $note->getUpdatedAt()->value()->format(Date::DATE_DEFAULT_RENDER_FORMAT),
                ],
                $this->notes
            ),
            'attachments' => array_map(
                static fn (Attachment $attachment) => [
                    'id' => $attachment->getId()->value(),
                    'fileName' => $attachment->getFileName()->value(),
                    'mimeType' => $attachment->getMimeType()->value(),
                    'sizeBytes' => $attachment->getSizeBytes()->value(),
                    'createdAt' => $attachment->getCreatedAt()->value()->format(Date::DATE_DEFAULT_RENDER_FORMAT),
                ],
                $this->attachments
            ),
        ];
    }
}
