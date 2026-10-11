<?php

declare(strict_types=1);

namespace App\Notes\Application\ListNotes;

use App\Core\Application\Query\QueryResponse;
use App\Notes\Domain\Model\Note;
use App\Shared\Domain\Time\Date;

class ListNotesResponse implements QueryResponse
{
    /**
     * @param Note[] $notes
     * @param array<string, int> $childCounts live children (notes and links) per note id
     */
    public function __construct(
        private array $notes,
        private array $childCounts,
        private int $total,
        private int $page,
        private int $size
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function getResult(): array
    {
        return [
            'meta' => [
                'page' => $this->page,
                'size' => $this->size,
                'total' => $this->total,
                'last_page' => (int) ceil($this->total / $this->size),
            ],
            'data' => array_map(
                fn (Note $note) => [
                    'id' => $note->getId()->value(),
                    'title' => $note->getTitle()->value(),
                    'icon' => $note->getIcon()?->value(),
                    'folder_id' => $note->getFolderId()?->value(),
                    'parent_note_id' => $note->getParentNoteId()?->value(),
                    'child_count' => $this->childCounts[$note->getId()->value()] ?? 0,
                    'pinned' => $note->isPinned(),
                    'is_encrypted' => $note->isEncrypted(),
                    'tags' => $note->getTags()->names(),
                    'created_at' => $note->getCreatedAt()->value()->format(Date::DATE_DEFAULT_RENDER_FORMAT),
                    'updated_at' => $note->getUpdatedAt()->value()->format(Date::DATE_DEFAULT_RENDER_FORMAT),
                ],
                $this->notes
            ),
        ];
    }
}
