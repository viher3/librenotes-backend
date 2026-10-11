<?php

declare(strict_types=1);

namespace App\Notes\Application\LinkDetail;

use App\Core\Application\Query\QueryResponse;
use App\Notes\Domain\Model\Link;
use App\Shared\Domain\Time\Date;

class LinkDetailResponse implements QueryResponse
{
    /**
     * @param list<array{type: string, id: string, title: string}> $path ancestors, root first
     */
    public function __construct(
        private Link $link,
        private array $path = []
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function getResult(): array
    {
        return [
            'id' => $this->link->getId()->value(),
            'title' => $this->link->getTitle()->value(),
            'icon' => $this->link->getIcon()?->value(),
            'url' => $this->link->getUrl()->value(),
            'note' => $this->link->getNote()?->value(),
            'folder_id' => $this->link->getFolderId()?->value(),
            'parent_note_id' => $this->link->getParentNoteId()?->value(),
            'path' => $this->path,
            'tags' => $this->link->getTags()->names(),
            'created_at' => $this->link->getCreatedAt()->value()->format(Date::DATE_DEFAULT_RENDER_FORMAT),
            'updated_at' => $this->link->getUpdatedAt()->value()->format(Date::DATE_DEFAULT_RENDER_FORMAT),
        ];
    }
}
