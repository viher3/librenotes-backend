<?php

declare(strict_types=1);

namespace App\Notes\Application\NoteDetail;

use App\Core\Application\Query\QueryResponse;
use App\Notes\Domain\Model\Attachment;
use App\Notes\Domain\Model\Note;
use App\Shared\Domain\Time\Date;

class NoteDetailResponse implements QueryResponse
{
    /**
     * @param Attachment[] $attachments
     * @param list<array{type: string, id: string, title: string}> $path ancestors, root first
     */
    public function __construct(
        private Note $note,
        private array $attachments = [],
        private array $path = [],
        private int $childCount = 0
    ) {
    }

    public function getResult(): array
    {
        return [
            'id' => $this->note->getId()->value(),
            'title' => $this->note->getTitle()->value(),
            'content' => $this->note->getContent()->value(),
            'folder_id' => $this->note->getFolderId()?->value(),
            'parent_note_id' => $this->note->getParentNoteId()?->value(),
            'path' => $this->path,
            'child_count' => $this->childCount,
            'pinned' => $this->note->isPinned(),
            'tags' => $this->note->getTags()->names(),
            'created_at' => $this->note->getCreatedAt()->value()->format(Date::DATE_DEFAULT_RENDER_FORMAT),
            'updated_at' => $this->note->getUpdatedAt()->value()->format(Date::DATE_DEFAULT_RENDER_FORMAT),
            'attachments' => array_map(
                static fn (Attachment $attachment) => [
                    'id' => $attachment->getId()->value(),
                    'file_name' => $attachment->getFileName()->value(),
                    'mime_type' => $attachment->getMimeType()->value(),
                    'size_bytes' => $attachment->getSizeBytes()->value(),
                    'created_at' => $attachment->getCreatedAt()->value()->format(Date::DATE_DEFAULT_RENDER_FORMAT),
                ],
                $this->attachments
            ),
        ];
    }
}
