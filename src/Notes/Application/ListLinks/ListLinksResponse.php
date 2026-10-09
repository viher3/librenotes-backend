<?php

declare(strict_types=1);

namespace App\Notes\Application\ListLinks;

use App\Core\Application\Query\QueryResponse;
use App\Notes\Domain\Model\Link;
use App\Shared\Domain\Time\Date;

class ListLinksResponse implements QueryResponse
{
    /**
     * @param Link[] $links
     */
    public function __construct(
        private array $links,
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
                static fn (Link $link) => [
                    'id' => $link->getId()->value(),
                    'title' => $link->getTitle()->value(),
                    'url' => $link->getUrl()->value(),
                    'note' => $link->getNote()?->value(),
                    'folder_id' => $link->getFolderId()?->value(),
                    'parent_note_id' => $link->getParentNoteId()?->value(),
                    'tags' => $link->getTags()->names(),
                    'created_at' => $link->getCreatedAt()->value()->format(Date::DATE_DEFAULT_RENDER_FORMAT),
                    'updated_at' => $link->getUpdatedAt()->value()->format(Date::DATE_DEFAULT_RENDER_FORMAT),
                ],
                $this->links
            ),
        ];
    }
}
