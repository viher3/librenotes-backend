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
     */
    public function __construct(
        private array $notes,
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
                static fn (Note $note) => [
                    'id' => $note->getId()->value(),
                    'title' => $note->getTitle()->value(),
                    'folder_id' => $note->getFolderId()?->value(),
                    'pinned' => $note->isPinned(),
                    'tags' => $note->getTags()->names(),
                    'created_at' => $note->getCreatedAt()->value()->format(Date::DATE_DEFAULT_RENDER_FORMAT),
                    'updated_at' => $note->getUpdatedAt()->value()->format(Date::DATE_DEFAULT_RENDER_FORMAT),
                ],
                $this->notes
            ),
        ];
    }
}
